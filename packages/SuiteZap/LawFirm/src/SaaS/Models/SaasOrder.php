<?php

namespace SuiteZap\LawFirm\SaaS\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\User;

/**
 * SaasOrder — Intenção de Compra do módulo SaaS.
 *
 * Criado ANTES de chamar o gateway Asaas. Contém o user_id
 * do solicitante, garantindo rastreabilidade completa.
 *
 * Status lifecycle: PENDING → PAID | EXPIRED | CANCELED
 *
 * @property int $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $type ai_credits | subscription
 * @property float $value Valor em R$ (1:1 com créditos)
 * @property string $asaas_payment_id
 * @property string $asaas_checkout_session_id
 * @property string $status PENDING | PAID | EXPIRED | CANCELED
 * @property string $description
 */
class SaasOrder extends Model
{
    protected $table = 'saas_orders';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'value',
        'asaas_payment_id',
        'asaas_checkout_session_id',
        'status',
        'description',
    ];

    // ── Relationships ────────────────────────────────────────

    /**
     * O usuário que iniciou o pedido.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Transações de ledger vinculadas a este pedido.
     */
    public function transactions()
    {
        return $this->hasMany(SaasTransaction::class, 'reference_id')
            ->where('reference_type', 'saas_order');
    }

    // ── Helpers ──────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }

    public function markAsPaid(string $asaasPaymentId): void
    {
        $this->update([
            'status'           => 'PAID',
            'asaas_payment_id' => $asaasPaymentId,
        ]);
    }
}
