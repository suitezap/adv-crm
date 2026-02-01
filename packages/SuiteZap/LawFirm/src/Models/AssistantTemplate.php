<?php

namespace SuiteZap\LawFirm\Models;

use Illuminate\Database\Eloquent\Model;
use SuiteZap\LawFirm\Contracts\AssistantTemplate as AssistantTemplateContract;

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
        'title',
        'category',
        'icon',
        'required_module',
        'description',
        'prompt_structure',
        'variables',
        'n8n_webhook_url',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'variables' => 'array',
    ];

    /**
     * Scope a query to only include templates for the current tenant or global ones.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCurrentTenant($query)
    {
        // Lógica: Traz templates GLOBAIS (tenant_id null) OU do cliente atual
        $tenantId = \SuiteZap\LawFirm\Services\MotherShipService::getTenantId();

        return $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id'); // Templates Globais
            if ($tenantId) {
                $q->orWhere('tenant_id', $tenantId); // Templates do Cliente
            }
        });
    }
}
