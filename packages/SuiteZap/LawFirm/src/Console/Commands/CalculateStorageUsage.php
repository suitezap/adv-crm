<?php

namespace SuiteZap\LawFirm\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use SuiteZap\LawFirm\Models\MotherShip\Tenant;
use SuiteZap\LawFirm\Models\MotherShip\Subscription;

class CalculateStorageUsage extends Command
{
    protected $signature = 'lawfirm:calc-storage';
    protected $description = 'Calcula o uso de disco S3 de cada tenant e atualiza o MotherShip';

    public function handle()
    {
        $tenants = Tenant::on('mothership')->get();
        $this->info("Iniciando auditoria de " . $tenants->count() . " tenants...");

        foreach ($tenants as $tenant) {
            $this->comment("Processando: " . $tenant->name);

            // 1. Configura Bucket
            if ($tenant->minio_bucket_name) {
                config(['filesystems.disks.s3.bucket' => $tenant->minio_bucket_name]);
            } else {
                continue; // Pula se não tiver bucket
            }

            // 2. Calcula
            try {
                $files = Storage::disk('s3')->allFiles();
                $totalBytes = 0;
                foreach ($files as $file) {
                    $totalBytes += Storage::disk('s3')->size($file);
                }

                // 3. Salva
                Subscription::on('mothership')
                    ->where('tenant_id', $tenant->id)
                    ->update(['current_usage_bytes' => $totalBytes]);

                $this->info(" > Uso: " . number_format($totalBytes / 1024 / 1024, 2) . " MB");

            } catch (\Exception $e) {
                $this->error(" > Erro ao ler bucket: " . $e->getMessage());
            }
        }
        $this->info("Auditoria concluída.");
    }
}
