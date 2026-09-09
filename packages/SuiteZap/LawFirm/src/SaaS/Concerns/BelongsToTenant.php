<?php

namespace SuiteZap\LawFirm\SaaS\Concerns;

use SuiteZap\LawFirm\SaaS\Scopes\TenantScope;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

/**
 * BelongsToTenant
 *
 * Uso: `use BelongsToTenant;` em Models de domínio compartilhado (mysql).
 * Aplica TenantScope global e preenche tenant_id no creating.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (empty($model->getAttribute('tenant_id'))) {
                $tenantId = MotherShipService::getTenantId();
                if (! empty($tenantId)) {
                    $model->setAttribute('tenant_id', $tenantId);
                }
            }
        });
    }
}
