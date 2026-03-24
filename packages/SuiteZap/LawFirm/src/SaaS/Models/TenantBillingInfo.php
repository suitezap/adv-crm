<?php

namespace SuiteZap\LawFirm\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TenantBillingInfo
 * 
 * Armazena os dados de faturamento do assinante no banco de dados MotherShip.
 * Esses dados são usados para preencher as requisições de pagamento ao Asaas.
 */
class TenantBillingInfo extends Model
{
    /**
     * O banco de dados centralizado do SaaS.
     */
    protected $connection = 'mothership';

    /**
     * A tabela associada ao model.
     */
    protected $table = 'tenant_billing_infos';

    /**
     * Os atributos que são mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'cpf_cnpj',
        'phone',
        'postal_code',
        'address',
        'address_number',
        'complement',
        'province',
        'city',
        'state',
    ];

    /**
     * Relationship: Billing Info pertence a um Tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
