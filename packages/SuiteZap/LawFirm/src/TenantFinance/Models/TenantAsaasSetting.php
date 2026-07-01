<?php

namespace SuiteZap\LawFirm\TenantFinance\Models;

use Illuminate\Database\Eloquent\Model;

class TenantAsaasSetting extends Model
{
    protected $table = 'tenant_asaas_settings';

    protected $fillable = [
        'api_key',
        'wallet_id',
        'environment',
        'webhook_token',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
        'webhook_token',
    ];

    /**
     * Retorna a URL base da API conforme o ambiente configurado.
     */
    public function getApiUrlAttribute(): string
    {
        return $this->environment === 'production'
            ? 'https://api.asaas.com'
            : 'https://api-sandbox.asaas.com';
    }
}
