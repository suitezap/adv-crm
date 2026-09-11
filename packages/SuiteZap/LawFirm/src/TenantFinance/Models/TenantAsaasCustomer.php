<?php

namespace SuiteZap\LawFirm\TenantFinance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SuiteZap\LawFirm\SaaS\Concerns\BelongsToTenant;

class TenantAsaasCustomer extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_asaas_customers';

    protected $fillable = [
        'tenant_id',
        'person_id',
        'lead_id',
        'asaas_customer_id',
        'name',
        'cpf_cnpj',
        'email',
        'phone',
    ];

    /**
     * Cobranças emitidas para este cliente.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(TenantInvoice::class, 'tenant_asaas_customer_id');
    }
}
