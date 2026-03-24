<?php

namespace SuiteZap\LawFirm\SaaS\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SaasFileService
{
    /**
     * Retorna o disco configurado para o Tenant (S3 ou Local)
     */
    protected function getDisk()
    {
        return Storage::disk(config('filesystems.default'));
    }

    /**
     * Verifica se o serviço de arquivo está disponível.
     */
    public function isAvailable(): bool
    {
        try {
            $disk = $this->getDisk();
            // Verifica se o disco foi instanciado corretamente
            return $disk !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function store(UploadedFile $file, string $path): string
    {
        $directory = dirname($path);
        $name = basename($path);

        $storedPath = $this->getDisk()->putFileAs($directory, $file, $name);

        if ($storedPath === false) {
            throw new \Exception("Erro ao salvar arquivo no disco.");
        }

        return $storedPath;
    }

    public function delete(string $path): bool
    {
        return $this->getDisk()->delete($path);
    }

    public function url(string $path): string
    {
        return $this->getDisk()->url($path);
    }

    public function exists(string $path): bool
    {
        return $this->getDisk()->exists($path);
    }

    public function deleteDirectory(string $path): bool
    {
        return $this->getDisk()->deleteDirectory($path);
    }

    /**
     * Retorna o conteúdo bruto de um arquivo do disco do Tenant.
     * Substitui Storage::get() diretamente para manter compliance multi-tenant.
     */
    public function get(string $path): ?string
    {
        try {
            return $this->getDisk()->get($path);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Armazena conteúdo bruto (string) em um path do disco do Tenant.
     * Útil para PDFs gerados em memória, JSONs e outros binários.
     */
    public function storeRaw(string $path, string $contents): bool
    {
        return $this->getDisk()->put($path, $contents);
    }

    /**
     * Retorna o MIME type de um arquivo no disco do Tenant.
     */
    public function mimeType(string $path): ?string
    {
        try {
            return $this->getDisk()->mimeType($path) ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
