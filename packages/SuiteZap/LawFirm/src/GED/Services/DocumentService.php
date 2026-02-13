<?php

namespace SuiteZap\LawFirm\GED\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SuiteZap\LawFirm\Models\Anexo;
use SuiteZap\LawFirm\Models\Processo;
use SuiteZap\LawFirm\Services\SaasStorageService;

class DocumentService
{
    protected $storageService;

    /**
     * Disco padrão para GED SaaS (configurado dinamicamente pelo MotherShipService)
     */
    protected const STORAGE_DISK = 's3';

    public function __construct(SaasStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Store a file for a specific process.
     *
     * @param UploadedFile $file
     * @param Processo $processo
     * @return Anexo
     * @throws \Exception
     */
    public function storeFile(UploadedFile $file, Processo $processo): Anexo
    {
        $fileSize = $file->getSize();

        // 1. Check Quota
        if (!$this->storageService->checkQuota($fileSize)) {
            throw new \Exception('Cota de disco excedida. Limite de armazenamento atingido.');
        }

        // 2. Generate Filename
        $processId = $processo->id;
        $randomHash = Str::random(7);
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanName = Str::slug($originalName);
        $extension = strtolower($file->getClientOriginalExtension());
        $finalName = "{$processId}-{$randomHash}_{$cleanName}.{$extension}";

        // 3. Store File (FORCE S3 - SaaS Compliance)
        // O LawFirmServiceProvider já configurou o disco 's3' com as credenciais do tenant
        $path = $file->storeAs(
            'processos/' . $processId,
            $finalName,
            self::STORAGE_DISK
        );

        // 4. Create Record
        $anexo = $processo->anexos()->create([
            'path' => $path,
            'nome_original' => $file->getClientOriginalName(),
            'tipo_mime' => $file->getMimeType(),
            'tamanho' => $fileSize,
        ]);

        // 5. Increment Usage
        $this->storageService->incrementUsage($fileSize);

        return $anexo;
    }

    /**
     * Delete a file by ID.
     *
     * @param int $documentId
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteFile($documentId): bool
    {
        $anexo = Anexo::findOrFail($documentId);
        $fileSize = $anexo->tamanho ?? 0;

        // 1. Delete from Storage (FORCE S3 - SaaS Compliance)
        if (Storage::disk(self::STORAGE_DISK)->exists($anexo->path)) {
            Storage::disk(self::STORAGE_DISK)->delete($anexo->path);
        }

        // 2. Delete Record
        $anexo->delete();

        // 3. Decrement Usage
        if ($fileSize > 0) {
            $this->storageService->decrementUsage($fileSize);
        }

        return true;
    }
}
