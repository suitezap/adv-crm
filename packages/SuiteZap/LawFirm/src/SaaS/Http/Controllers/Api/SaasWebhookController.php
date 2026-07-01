<?php

namespace SuiteZap\LawFirm\SaaS\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Admin\Http\Controllers\Controller;

class SaasWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests to update SaaS subscription.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSubscription(Request $request)
    {
        // 1. Security Check
        $token = $request->header('X-SAAS-TOKEN');
        // Lê diretamente do BD Mothership garantindo sync com painel global
        $secret = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getAppConfig('api_secret');

        if (empty($secret)) {
            $secret = config('lawfirm.saas.webhook_secret');
        }

        if (! $secret || $token !== $secret) {
            Log::warning('SaaS Webhook: Unauthorized access attempt.', ['ip' => $request->ip()]);

            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // 2. Validation
        $action = $request->input('action');
        $data = $request->input('data', []);

        if (! $action) {
            return response()->json(['success' => false, 'message' => 'Missing action parameter'], 400);
        }

        Log::info("SaaS Webhook Received: {$action}", $data);

        try {
            switch ($action) {
                case 'update_plan':
                    if (isset($data['status'])) {
                        $this->setConfig('lawfirm.saas.plan_status', $data['status']);
                    }
                    if (isset($data['expires_at'])) {
                        $this->setConfig('lawfirm.saas.expires_at', $data['expires_at']);
                    }
                    if (isset($data['plan_name'])) {
                        $this->setConfig('lawfirm.saas.plan_name', $data['plan_name']);
                    }
                    break;

                case 'topup_tokens':
                    $amount = (int) ($data['credits'] ?? 0);
                    if ($amount > 0) {
                        $currentConfig = DB::table('core_config')
                            ->where('code', 'lawfirm.saas.ai.credits')
                            ->first();

                        $current = $currentConfig ? (int) $currentConfig->value : 0;
                        $newTotal = $current + $amount;

                        $this->setConfig('lawfirm.saas.ai.credits', $newTotal);
                    }
                    break;

                case 'add_storage':
                    $bytes = (int) ($data['limit_bytes'] ?? 0);
                    if ($bytes > 0) {
                        $this->setConfig('lawfirm.saas.storage.limit', $bytes);
                    }
                    break;

                default:
                    return response()->json(['success' => false, 'message' => 'Unknown action'], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Processed successfully',
                'action'  => $action,
            ]);
        } catch (\Exception $e) {
            Log::error('SaaS Webhook Error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Helper to update or insert config value directly in DB.
     */
    protected function setConfig(string $code, mixed $value): void
    {
        DB::table('core_config')->updateOrInsert(
            ['code' => $code],
            [
                'value'        => $value,
                'channel_code' => null,
                'locale_code'  => null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]
        );
    }
}
