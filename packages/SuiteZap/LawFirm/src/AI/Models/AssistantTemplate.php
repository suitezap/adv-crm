<?php

namespace SuiteZap\LawFirm\AI\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SuiteZap\LawFirm\Contracts\AssistantTemplate as AssistantTemplateContract;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SuiteCoinService;

class AssistantTemplate extends Model implements AssistantTemplateContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'mothership';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lawfirm_assistant_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tenant_id',
        'slug',
        'category',
        'area',
        'title',
        'description',
        'icon',
        'prompt_structure',
        'variables',
        'n8n_webhook_url',
        'required_module',
        'is_active',
        'base_cost_brl',
        'markup_factor',
        'price_virtual',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active'      => 'boolean',
        'variables'      => 'array',
        'base_cost_brl'  => 'decimal:4',
        'markup_factor'  => 'decimal:4',
        'price_virtual'  => 'decimal:4',
    ];

    /**
     * Retorna o custo do assistente já convertido para SuiteCoins (Ƶ).
     * Usado exclusivamente na camada de UI/Blade.
     * DB armazena price_virtual em BRL (paridade 1:1 com suitecoin_balance).
     */
    public function getPriceVirtualSuiteCoinsAttribute(): float
    {
        return SuiteCoinService::toVirtual(
            (float) ($this->attributes['price_virtual'] ?? 0)
        );
    }

    /**
     * Scope a query to only include templates for the current tenant or global ones.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeForTenant($query, $tenantId = null)
    {
        // Lógica: Traz templates GLOBAIS (tenant_id null) OU do cliente atual
        $tenantId = MotherShipService::getTenantId();

        return $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id'); // Templates Globais
            if ($tenantId) {
                $q->orWhere('tenant_id', $tenantId); // Templates do Cliente
            }
        });
    }
}
