<?php

namespace SuiteZap\LawFirm\GED\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SuiteZap\LawFirm\Legal\Models\Anexo;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\SaaS\Services\SaasFileService;
use SuiteZap\LawFirm\SaaS\Services\SaasStorageService;

class DocumentService
{
    protected $storageService;

    protected $fileService;

    public function __construct(
        SaasStorageService $storageService,
        SaasFileService $fileService
    ) {
        $this->storageService = $storageService;
        $this->fileService = $fileService;
    }

    /**
     * Store a file for a specific process.
     *
     * @throws \Exception
     */
    public function storeFile(UploadedFile $file, Processo $processo): Anexo
    {
        // 0. Safety Check - MotherShip Storage Injection
        if (! $this->fileService->isAvailable()) {
            throw new \Exception('Erro de Infraestrutura: Serviço de armazenamento não disponível (S3/Local falhou).');
        }

        $fileSize = $file->getSize();

        // 1. Check Quota
        if (! $this->storageService->checkQuota($fileSize)) {
            throw new \Exception('Cota de disco excedida. Limite de armazenamento atingido.');
        }

        // 2. Generate Filename
        $processId = $processo->id;
        $randomHash = Str::random(7);
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanName = Str::slug($originalName);
        $extension = strtolower($file->getClientOriginalExtension());
        $finalName = "{$processId}-{$randomHash}_{$cleanName}.{$extension}";

        // 3. Store File — Zero-Copy Hierarchy (v3.45)
        // Se o processo pertence a um caso, centralizar na pasta do Caso para compartilhamento.
        if ($processo->caso_id) {
            $fullPath = 'casos/'.$processo->caso_id.'/documents/'.$finalName;
        } else {
            $fullPath = 'processos/'.$processId.'/'.$finalName;
        }

        $path = $this->fileService->store($file, $fullPath);

        // 4. Create Record (with caso_id for Zero-Copy visibility)
        $anexo = $processo->anexos()->create([
            'path'          => $path,
            'nome_original' => $file->getClientOriginalName(),
            'tipo_mime'     => $file->getMimeType(),
            'tamanho'       => $fileSize,
            'caso_id'       => $processo->caso_id,
        ]);

        // 5. Increment Usage
        $this->storageService->incrementUsage($fileSize);

        return $anexo;
    }

    /**
     * Delete a file by ID.
     *
     * @param  int  $documentId
     *
     * @throws ModelNotFoundException
     */
    public function deleteFile($documentId): bool
    {
        $anexo = Anexo::findOrFail($documentId);
        $fileSize = $anexo->tamanho ?? 0;

        // 1. Delete from Storage (Updated to use SaasFileService)
        if ($this->fileService->exists($anexo->path)) {
            $this->fileService->delete($anexo->path);
        }

        // 2. Delete Record
        $anexo->delete();

        // 3. Decrement Usage
        if ($fileSize > 0) {
            $this->storageService->decrementUsage($fileSize);
        }

        return true;
    }

    /**
     * Process uploads from request (single 'anexo' or multiple 'anexos').
     *
     * @param  array|Request  $request
     */
    public function processUploads(Processo $processo, $request): array
    {
        $files = [];

        // Handle Request object or array data
        if ($request instanceof Request) {
            if ($request->hasFile('anexos')) {
                $files = $request->file('anexos');
            } elseif ($request->hasFile('anexo')) {
                $files = [$request->file('anexo')];
            }
        } elseif (is_array($request)) {
            // Fallback if passed as array (less common for files but possible)
            $files = $request['anexos'] ?? ($request['anexo'] ? [$request['anexo']] : []);
        }

        $uploadedDocs = [];

        foreach ($files as $file) {
            if (! $file->isValid()) {
                continue;
            }

            try {
                // Delegate to existing storeFile which determines path, naming, quota, etc.
                $uploadedDocs[] = $this->storeFile($file, $processo);
            } catch (\Exception $e) {
                // Log error but continue processing other files?
                // matched user snippet behavior of skipping invalid, but here we catch exceptions.
                Log::error('DocumentService::processUploads - Error storing file: '.$e->getMessage());
            }
        }

        return $uploadedDocs;
    }
}
