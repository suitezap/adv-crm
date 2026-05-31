<?php

namespace SuiteZap\LawFirm\SaaS\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SuiteCoinService;

/**
 * SaasTransactionsDataGrid — Exibe o extrato de movimentações financeiras (débitos/créditos)
 * de créditos da assinatura SaaS, estritamente isolado por tenant.
 *
 * SEGURANÇA: Sempre filtra por tenant_id do tenant atual.
 * Nunca exibe transações de outros tenants.
 */
class SaasTransactionsDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';
    protected $sortColumn = 'id';
    protected $sortOrder = 'desc';

    public function prepareQueryBuilder()
    {
        // Usa o tenant_id canônico do serviço — nunca confia em input externo
        $tenantId = MotherShipService::getTenantId();

        $queryBuilder = DB::table('saas_transactions')
            ->leftJoin('users', 'saas_transactions.user_id', '=', 'users.id')
            // Filtro duplo: tenant_id correto E não-nulo (exclui registros históricos sem escopo)
            ->where('saas_transactions.tenant_id', $tenantId)
            ->whereNotNull('saas_transactions.tenant_id')
            ->whereIn('saas_transactions.type', ['debit', 'credit']);

        $userIds = bouncer()->getAuthorizedUserIds();
        if (! empty($userIds)) {
            $queryBuilder->whereIn('saas_transactions.user_id', $userIds);
        }

        $queryBuilder->select(
                'saas_transactions.id',
                'saas_transactions.type',
                'saas_transactions.amount',
                'saas_transactions.balance_after',
                'saas_transactions.service_type',
                'saas_transactions.description',
                'users.name as user_name',
                'saas_transactions.created_at'
            );

        $this->addFilter('id', 'saas_transactions.id');
        $this->addFilter('type', 'saas_transactions.type');
        $this->addFilter('service_type', 'saas_transactions.service_type');
        $this->addFilter('user_name', 'users.name');

        $this->setQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => '#',
            'type'       => 'integer',
            'sortable'   => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'    => 'type',
            'label'    => 'Tipo',
            'type'     => 'string',
            'sortable' => true,
            'closure'  => function ($row) {
                if ($row->type === 'credit') {
                    return '<span class="badge badge-round badge-success">Crédito</span>';
                }
                return '<span class="badge badge-round badge-danger">Débito</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'service_type',
            'label'      => 'Serviço',
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'description',
            'label'      => 'Descrição',
            'type'       => 'string',
            'sortable'   => false,
            'searchable' => true,
            'closure'    => function ($row) {
                // Remove o sufixo de custo unitário: " — Ƶ X,XX" ou " — Custo: Ƶ X,XX"
                return preg_replace('/\s*[—\-]{1,2}\s*(Custo:\s*)?[Ƶ#]?\s*[\d.,]+\s*$/u', '', $row->description);
            },
        ]);

        $this->addColumn([
            'index'    => 'amount',
            'label'    => 'Valor',
            'type'     => 'string',
            'sortable' => true,
            'closure'  => function ($row) {
                if ($row->type === 'credit') {
                    // Depósito: exibe o valor real pago em R$
                    return 'R$ ' . number_format((float) $row->amount, 2, ',', '.');
                }
                // Débito de consumo: exibe em Ƶ
                return SuiteCoinService::formatFromBrl((float) $row->amount);
            },
        ]);

        $this->addColumn([
            'index'    => 'balance_after',
            'label'    => 'Saldo (Ƶ)',
            'type'     => 'string',
            'sortable' => false,
            'closure'  => function ($row) {
                if ($row->balance_after === null)
                    return '—';
                return SuiteCoinService::formatFromBrl((float) $row->balance_after);
            },
        ]);

        $this->addColumn([
            'index'      => 'user_name',
            'label'      => 'Usuário',
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
            'closure'    => fn($row) => $row->user_name ?: '(Sistema)',
        ]);

        $this->addColumn([
            'index'    => 'created_at',
            'label'    => 'Data/Hora',
            'type'     => 'datetime',
            'sortable' => true,
            'closure'  => fn($row) => core()->formatDate($row->created_at, 'd/m/Y H:i'),
        ]);
    }

    public function prepareActions()
    {
    }
}
