<?php

namespace SuiteZap\LawFirm\TenantFinance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SuiteZap\LawFirm\Financial\Models\Financial;
use SuiteZap\LawFirm\Legal\Models\Processo;

class TenantInvoice extends Model
{
    protected $table = 'tenant_invoices';

    protected $fillable = [
        'processo_id',
        'financial_id',
        'tenant_asaas_customer_id',
        'asaas_payment_id',
        'asaas_installment_id',
        'asaas_subscription_id',
        'type',
        'description',
        'value',
        'installment_count',
        'installment_value',
        'billing_type',
        'status',
        'due_date',
        'payment_date',
        'invoice_url',
        'pix_qrcode',
    ];

    protected $casts = [
        'value'             => 'decimal:2',
        'installment_value' => 'decimal:2',
        'due_date'          => 'date',
        'payment_date'      => 'datetime',
    ];

    // ── Relationships ────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(TenantAsaasCustomer::class, 'tenant_asaas_customer_id');
    }

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    public function financial(): BelongsTo
    {
        return $this->belongsTo(Financial::class);
    }

    // ── Helpers ──────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH']);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'OVERDUE';
    }

    public function markAsPaid(?string $paymentDate = null): void
    {
        $this->update([
            'status'       => 'RECEIVED',
            'payment_date' => $paymentDate ?? now(),
        ]);
    }
}
