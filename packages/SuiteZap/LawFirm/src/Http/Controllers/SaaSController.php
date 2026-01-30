<?php

namespace SuiteZap\LawFirm\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use SuiteZap\LawFirm\Services\MotherShipService;
use Webkul\User\Models\User;
use Carbon\Carbon;

class SaaSController extends Controller
{
    use DispatchesJobs, ValidatesRequests;

    /**
     * Display the subscription dashboard.
     */
    public function index()
    {
        // 1. Busca dados do MotherShip
        $subscription = MotherShipService::getCurrentSubscription();

        // 2. Coleta métricas locais
        $usersCount = User::count();

        // Leitura do uso de disco do MotherShip (valor pré-calculado pelo comando lawfirm:calc-storage)
        // Passa os bytes brutos para a view evitar perda de precisão na conversão
        $storageUsedBytes = $subscription->current_usage_bytes ?? 0;

        // 3. Cálculos de Datas
        $daysLeft = 0;
        $progressDate = 0;

        if ($subscription && $subscription->expires_at) {
            $expires = Carbon::parse($subscription->expires_at);
            $daysLeft = now()->diffInDays($expires, false);

            // Lógica da barra de progresso (Assumindo ciclo de 30 dias)
            $totalDays = 30;
            $daysPassed = $totalDays - $daysLeft;
            $progressDate = ($daysPassed / $totalDays) * 100;
        }

        return view('lawfirm::subscription.index', compact(
            'subscription',
            'usersCount',
            'storageUsedBytes',
            'daysLeft',
            'progressDate'
        ));
    }

    /**
     * Calcula o uso de armazenamento em GB.
     */
    private function calculateStorageUsage()
    {
        // Pasta onde os arquivos públicos são armazenados (processos, anexos, etc.)
        $storagePath = storage_path('app/public');

        if (!is_dir($storagePath)) {
            return 0;
        }

        try {
            $bytes = $this->getDirectorySize($storagePath);
            // Converte bytes para GB
            return round($bytes / 1024 / 1024 / 1024, 2);
        } catch (\Exception $e) {
            \Log::warning('SAAS: Erro ao calcular uso de disco: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcula o tamanho de um diretório recursivamente.
     */
    private function getDirectorySize($path)
    {
        $size = 0;

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
