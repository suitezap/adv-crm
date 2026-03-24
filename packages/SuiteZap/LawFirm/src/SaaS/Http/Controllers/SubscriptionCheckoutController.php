<?php

namespace SuiteZap\LawFirm\SaaS\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use SuiteZap\LawFirm\SaaS\Services\AsaasService;

/**
 * SubscriptionCheckoutController — Skinny Controller
 *
 * Responsável por criar sessões de Checkout Asaas via API /v3/checkouts.
 * Toda a lógica de negócio está em AsaasService.
 *
 * Fluxo:
 *  1. Lê dados do escritório do core_config (via AsaasService::getOwnerCustomerData)
 *  2. Cria checkout no Asaas (retorna URL)
 *  3. Retorna JSON {"success": true, "checkoutUrl": "..."}
 *  4. Frontend redireciona o usuário para a URL do checkout
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

            // Nome do plano para exibição no checkout
            $planName = $request->input('plan_name', 'Plano LawFirm CRM');

            $checkoutUrl = AsaasService::createSubscriptionCheckout(
                $request->input('plan_id'),
                (float) $request->input('price'),
                $planName,
                $ownerData
            );

            return response()->json([
                'success'     => true,
                'checkoutUrl' => $checkoutUrl,
            ]);
        } catch (\Exception $e) {
            \Log::error('SubscriptionCheckoutController::checkoutPlan falhou: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Inicia o checkout avulso para compra de pacote de Créditos de IA.
     *
     * POST admin/saas/checkout/credits
     * Body: { credits: int, price: float }
     */
    public function checkoutCredits(Request $request)
    {
        $request->validate([
            'credits'        => 'required|integer|min:1',
            'price'          => 'required|numeric|min:0.50',
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

            // Segurança: O preço no backend SEMPRE dita a regra (R$ 1 = 100 Créditos / R$ 0,01 = 1 Crédito)
            $credits = (int) $request->input('credits');
            $calculatedPrice = max(0.50, $credits / 100);

            $checkoutUrl = AsaasService::createCreditCheckout(
                $credits,
                $calculatedPrice,
                $ownerData,
                $request->input('payment_method')
            );

            return response()->json([
                'success'     => true,
                'checkoutUrl' => $checkoutUrl,
            ]);
        } catch (\Exception $e) {
            \Log::error('SubscriptionCheckoutController::checkoutCredits falhou: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
