<?php

namespace SuiteZap\LawFirm\SaaS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SuiteZap\LawFirm\SaaS\Models\SaasOrder;
use SuiteZap\LawFirm\SaaS\Services\AsaasService;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

/**
 * SubscriptionCheckoutController — Skinny Controller
 *
 * Responsável por criar sessões de Checkout Asaas via API /v3/checkouts.
 * Toda a lógica de negócio está em AsaasService.
 *
 * Fluxo (v3.21 — Order-based):
 *  1. Cria uma SaasOrder com status PENDING e user_id do usuário logado
 *  2. Chama o Asaas com externalReference = "order_{id}"
 *  3. Salva o checkout_session_id do Asaas na order
 *  4. Retorna JSON {"success": true, "checkoutUrl": "..."}
 *  5. Frontend redireciona o usuário para a URL do checkout
 */
class SubscriptionCheckoutController extends Controller
{
    /**
     * Inicia o checkout para assinatura mensal recorrente (upgrade de plano).
     *
     * POST admin/saas/checkout/plan
     * Body: { plan_id: string, price: float, plan_name?: string }
     */
    public function checkoutPlan(Request $request)
    {
        $request->validate([
            'plan_id'  => 'required|string',
            'price'    => 'required|numeric|min:1',
        ]);

        try {
            $ownerData = AsaasService::getOwnerCustomerData();

            if (empty($ownerData['cpfCnpj'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Por favor, preencha o CPF/CNPJ do escritório em Configurações → Personalização antes de efetuar o pagamento.',
                ], 422);
            }

            $tenantId = MotherShipService::getTenantId();
            $price = (float) $request->input('price');
            $planId = $request->input('plan_id');
            $planName = $request->input('plan_name', 'Plano LawFirm CRM');

            // ── 1. Cria a Intenção de Compra (Order) ─────────────────
            $order = SaasOrder::create([
                'tenant_id'   => $tenantId,
                'user_id'     => auth()->id(),
                'type'        => 'subscription',
                'value'       => $price,
                'status'      => 'PENDING',
                'description' => "Checkout de Assinatura: {$planName} (R$ ".number_format($price, 2, ',', '.').')',
            ]);

            // ── 2. Cria o Checkout no Asaas ──────────────────────────
            $result = AsaasService::createSubscriptionCheckout(
                $planId,
                $price,
                $planName,
                $ownerData,
                $order->id
            );

            // ── 3. Salva o ID da sessão de checkout na Order ─────────
            $order->update([
                'asaas_checkout_session_id' => $result['session_id'],
            ]);

            return response()->json([
                'success'     => true,
                'checkoutUrl' => $result['checkout_url'],
            ]);
        } catch (\Exception $e) {
            \Log::error('SubscriptionCheckoutController::checkoutPlan falhou: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Inicia o checkout avulso para compra de Créditos de IA.
     *
     * POST admin/saas/checkout/credits
     * Body: { value: float, payment_method: string }
     *
     * Proporção fixa: R$ 1,00 = 1 Crédito de IA.
     * O backend SEMPRE recalcula — nunca confia no frontend.
     */
    public function checkoutCredits(Request $request)
    {
        $request->validate([
            'value'          => 'required|numeric|min:1',
            'payment_method' => 'required|in:PIX,CREDIT_CARD,CREDIT_CARD_INSTALLMENT',
        ]);

        try {
            $ownerData = AsaasService::getOwnerCustomerData();

            if (empty($ownerData['cpfCnpj'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Por favor, preencha o CPF/CNPJ do escritório na aba Faturamento antes de comprar créditos.',
                ], 422);
            }

            $tenantId = MotherShipService::getTenantId();

            // Proporção 1:1 — R$ 1,00 = 1 Crédito. Backend é a fonte da verdade.
            $value = max(1.00, (float) $request->input('value'));

            // ── 1. Cria a Intenção de Compra (Order) ─────────────────
            $order = SaasOrder::create([
                'tenant_id'   => $tenantId,
                'user_id'     => auth()->id(),
                'type'        => 'ai_credits',
                'value'       => $value,
                'status'      => 'PENDING',
                'description' => 'Compra de R$ '.number_format($value, 2, ',', '.').' em Créditos de IA',
            ]);

            // ── 2. Cria o Checkout no Asaas ──────────────────────────
            $result = AsaasService::createCreditCheckout(
                $value,
                $ownerData,
                $request->input('payment_method'),
                $order->id
            );

            // ── 3. Salva o ID da sessão de checkout na Order ─────────
            $order->update([
                'asaas_checkout_session_id' => $result['session_id'],
            ]);

            return response()->json([
                'success'     => true,
                'checkoutUrl' => $result['checkout_url'],
            ]);
        } catch (\Exception $e) {
            \Log::error('SubscriptionCheckoutController::checkoutCredits falhou: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
