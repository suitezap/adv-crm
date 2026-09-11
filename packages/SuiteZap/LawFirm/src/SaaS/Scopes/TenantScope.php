<?php

namespace SuiteZap\LawFirm\SaaS\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

/**
 * TenantScope
 *
 * Global scope que restringe toda query de domínio ao tenant_id da sessão
 * ativa (config lawfirm.tenant_id). Tabelas sem tenant_id preenchido (legado
 * em backfill) permanecem visíveis até a migration de NOT NULL — sem vazar
 * entre tenants porque a escrita já preenche o tenant.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = MotherShipService::getTenantId();

        if (! empty($tenantId)) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }
}
