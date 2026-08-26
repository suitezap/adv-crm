<?php

namespace SuiteZap\LawFirm\SaaS\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use SuiteZap\LawFirm\SaaS\Services\AsaasService;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SaasFileService;
use Webkul\User\Models\User;

class SaaSController extends Controller
{
    use DispatchesJobs, ValidatesRequests;

    /**
     * Display the subscription dashboard.
     */
    public function index(Request $request)
    {
        $tenantId = MotherShipService::getTenantId() ?? 'default';
        $syncCacheKey = 'asaas_sync_last_run_'.$tenantId;
        $subCacheKey = "tenant_{$tenantId}_subscription";

        // Pagamento retornado do checkout — força sync imediato e invalida cache da subscription
        $isPaymentReturn = $request->has('payment');
        if ($isPaymentReturn) {
            Cache::forget($syncCacheKey);    // ignora throttle
            Cache::forget($subCacheKey);     // subscription atualizada após sync
            Cache::forget('asaas_node_config');
        }

        // Sincroniza pagamentos pendentes (throttled: 1x a cada 60s fora do fluxo de retorno)
        if (! Cache::has($syncCacheKey)) {
            AsaasService::syncTenantPayments();
            Cache::put($syncCacheKey, true, 60); // throttle 60 segundos
            // Invalida cache da subscription para refletir créditos adicionados pelo sync
            Cache::forget($subCacheKey);
        }

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

        // 4. Busca Assistentes de IA disponíveis para o Tenant e a classificação do Tenant
        $availableAssistants = collect();
        $tenantClassification = 'Padrão';

        if ($subscription) {
            $tenantId = MotherShipService::getTenantId();
            $activeModules = $subscription->active_modules ?? [];

            if ($tenantId) {
                $availableAssistants = MotherShipService::getAvailableAssistants($tenantId, $activeModules);

                // Busca as configurações do Tenant para pegar a classificação
                $tenantConfig = MotherShipService::getTenantConfig();
                if ($tenantConfig && ! empty($tenantConfig->classification)) {
                    $tenantClassification = $tenantConfig->classification;
                }
            }
        }

        return view('lawfirm::subscription.index', compact(
            'subscription',
            'usersCount',
            'storageUsedBytes',
            'daysLeft',
            'progressDate',
            'availableAssistants',
            'tenantClassification'
        ));
    }

    /**
     * Calcula o uso de armazenamento em GB.
     */
    private function calculateStorageUsage()
    {
        // Pasta onde os arquivos públicos são armazenados (processos, anexos, etc.)
        $storagePath = storage_path('app/public');

        if (! is_dir($storagePath)) {
            return 0;
        }

        try {
            $bytes = $this->getDirectorySize($storagePath);

            // Converte bytes para GB
            return round($bytes / 1024 / 1024 / 1024, 2);
        } catch (\Exception $e) {
            \Log::warning('SAAS: Erro ao calcular uso de disco: '.$e->getMessage());

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

    /**
     * Diagnóstico de conectividade com o bucket S3/MinIO do Tenant.
     *
     * CC fix (v3.49): Lógica movida da closure de rota (que usava Storage:: diretamente)
     * para este método, delegando ao SaasFileService::testConnection() (Regra 2.2).
     *
     * Protegido: retorna 403 em produção (APP_DEBUG=false).
     */
    public function testS3Connection(SaasFileService $fileService)
    {
        if (! config('app.debug')) {
            return response()->json([
                'error' => 'Rota de diagnóstico disponível apenas em modo debug (APP_DEBUG=true).',
            ], 403);
        }

        $result = $fileService->testConnection();

        $statusCode = ($result['status'] === 'sucesso') ? 200 : 500;

        return response()->json($result, $statusCode);
    }
}
