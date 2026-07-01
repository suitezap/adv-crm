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
            // Se falhou e estivermos no S3, tentamos verificar se o bucket existe e criá-lo (JIT)
            if (config('filesystems.default') === 's3') {
                $this->ensureBucketExists();
                $storedPath = $this->getDisk()->putFileAs($directory, $file, $name);
            }

            if ($storedPath === false) {
                throw new \Exception('Erro ao salvar arquivo no disco.');
            }
        }

        return $storedPath;
    }

    public function delete(string $path): bool
    {
        return $this->getDisk()->delete($path);
    }

    public function url(string $path): string
    {
        if (config('filesystems.default') === 's3') {
            return $this->getSignedUrl($path);
        }

        return $this->getDisk()->url($path);
    }

    /**
     * Retorna uma URL assinada temporária válida para o S3/MinIO.
     * Necessário para buckets privados (Padrão de segurança de tenants).
     */
    public static function getSignedUrl(string $path, int $minutes = 60): string
    {
        if (config('filesystems.default') === 's3') {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes($minutes));
        }

        return Storage::disk(config('filesystems.default'))->url($path);
    }

    /**
     * Retorna a URL do arquivo somente se ele existir no disco.
     * Para disco local em dev: evita requests 404 quando o arquivo foi enviado
     * apenas no ambiente de produção (S3/MinIO).
     * Retorna null se o arquivo não existir — o template deve exibir o logo padrão.
     */
    public static function safeUrl(string $path, int $minutes = 60): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = config('filesystems.default');

        if ($disk === 's3') {
            // No S3/MinIO assumimos que o arquivo existe (foi enviado em produção)
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes($minutes));
        }

        // Disco local: verifica existência antes de gerar URL para evitar 404
        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        return Storage::disk($disk)->url($path);
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
        $result = $this->getDisk()->put($path, $contents);

        if ($result === false) {
            // Tentativa de criação JIT do bucket caso falhe inicialmente
            if (config('filesystems.default') === 's3') {
                $this->ensureBucketExists();
                $result = $this->getDisk()->put($path, $contents);
            }
        }

        return $result;
    }

    /**
     * Garante a existência do bucket no S3/MinIO (Criação JIT)
     */
    public function ensureBucketExists(): void
    {
        try {
            if (config('filesystems.default') !== 's3') {
                return;
            }

            $disk = $this->getDisk();

            /** @var \Aws\S3\S3Client $client */
            $client = $disk->getClient();
            $bucket = config('filesystems.disks.s3.bucket');

            if (! $bucket) {
                return;
            }

            try {
                $client->headBucket(['Bucket' => $bucket]);
            } catch (\Aws\S3\Exception\S3Exception $e) {
                // Se bucket não existir, cria
                if ($e->getStatusCode() == 404 || str_contains((string) $e->getAwsErrorCode(), 'NoSuchBucket') || str_contains((string) $e->getAwsErrorCode(), 'NotFound')) {
                    $client->createBucket(['Bucket' => $bucket]);
                    \Illuminate\Support\Facades\Log::info("SAAS INFO: Bucket '{$bucket}' criado automaticamente (JIT) no S3/MinIO.");
                } else {
                    throw $e;
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SAAS ERRO: Falha ao garantir/criar bucket no MinIO: '.$e->getMessage());
        }
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

    /**
     * Lista todos os arquivos recursivamente no bucket do Tenant.
     * Permite que Console Commands auditem uso de storage sem chamar Storage:: diretamente.
     *
     * @return array<string>
     */
    public function listAll(string $directory = '/'): array
    {
        try {
            return $this->getDisk()->allFiles($directory);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SaasFileService::listAll falhou: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Retorna o tamanho em bytes de um arquivo no bucket do Tenant.
     */
    public function size(string $path): int
    {
        try {
            return $this->getDisk()->size($path);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Verifica conectividade com o bucket do Tenant (diagnóstico).
     * Cria e apaga um arquivo temporário de teste.
     */
    public function testConnection(): array
    {
        try {
            $disk = $this->getDisk();
            $diskName = config('filesystems.default');
            $testFile = 'diagnostico/test-connection-'.time().'.txt';
            $content = 'Conexão S3/MinIO OK - '.now()->toIso8601String();

            $disk->put($testFile, $content);
            $url = $disk->url($testFile);
            $exists = $disk->exists($testFile);
            $disk->delete($testFile); // limpa o arquivo de teste

            return [
                'status'       => 'sucesso',
                'disk'         => $diskName,
                'bucket'       => config("filesystems.disks.{$diskName}.bucket") ?? 'N/A',
                'endpoint'     => config("filesystems.disks.{$diskName}.endpoint") ?? 'N/A',
                'file_created' => $exists,
                'url_sample'   => $url,
                'message'      => 'Bucket configurado e operacional.',
            ];
        } catch (\Exception $e) {
            return [
                'status'  => 'erro',
                'message' => $e->getMessage(),
                'trace'   => app()->hasDebugModeEnabled() ? $e->getTraceAsString() : '[oculto em produção]',
            ];
        }
    }
}
