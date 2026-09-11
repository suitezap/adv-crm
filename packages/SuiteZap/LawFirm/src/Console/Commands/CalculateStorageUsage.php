<?php

namespace SuiteZap\LawFirm\Console\Commands;

use Illuminate\Console\Command;
use SuiteZap\LawFirm\SaaS\Models\Subscription;
use SuiteZap\LawFirm\SaaS\Models\Tenant;
use SuiteZap\LawFirm\SaaS\Services\SaasFileService;

/**
 * Calcula o uso de disco S3/MinIO de cada tenant e atualiza o MotherShip.
 *
 * Conformidade (Regra 2.2 SKILL.md):
 * Este comando NÃO contém nenhuma chamada direta a Storage::. Todo acesso ao
 * filesystem é feito exclusivamente via SaasFileService::listAll() e
 * SaasFileService::size(), garantindo o isolamento multi-tenant do bucket S3/MinIO.
 *
 * @see SuiteZap\LawFirm\SaaS\Services\SaasFileService
 */
class CalculateStorageUsage extends Command
{
    protected $signature = 'lawfirm:calc-storage';

    protected $description = 'Calcula o uso de disco S3 de cada tenant e atualiza o MotherShip';

    public function handle()
    {
        $tenants = Tenant::on('mothership')->get();
        $this->info('Iniciando auditoria de '.$tenants->count().' tenants...');

        foreach ($tenants as $tenant) {
            $this->comment('Processando: '.$tenant->name);

            // 1. Pula se não tiver bucket configurado
            if (! $tenant->minio_bucket_name) {
                $this->warn('  > Sem bucket configurado — ignorado.');

                continue;
            }

            // 2. Configura bucket do tenant temporariamente para o loop
            config(['filesystems.disks.s3.bucket' => $tenant->minio_bucket_name]);

            // 3. Usa SaasFileService — mantém compliance multi-tenant (Regra 2.2)
            $fileService = app(SaasFileService::class);

            try {
                $files = $fileService->listAll('/');
                $totalBytes = 0;

                foreach ($files as $file) {
                    $totalBytes += $fileService->size($file);
                }

                // 4. Salva no MotherShip
                Subscription::on('mothership')
                    ->where('tenant_id', $tenant->id)
                    ->update(['current_usage_bytes' => $totalBytes]);

                $this->info('  > Uso: '.number_format($totalBytes / 1024 / 1024, 2).' MB');

            } catch (\Exception $e) {
                $this->error('  > Erro ao auditar bucket: '.$e->getMessage());
            }
        }

        $this->info('Auditoria concluída.');
    }
}
