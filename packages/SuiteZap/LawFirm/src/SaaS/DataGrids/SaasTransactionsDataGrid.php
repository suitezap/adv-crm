<?php

namespace SuiteZap\LawFirm\SaaS\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

/**
 * SaasTransactionsDataGrid — Exibe o extrato de movimentações financeiras (débitos/créditos)
 * de créditos da assinatura SaaS.
 */
class SaasTransactionsDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';
    protected $sortColumn = 'id';
    protected $sortOrder = 'desc';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('saas_transactions')
            ->leftJoin('users', 'saas_transactions.user_id', '=', 'users.id')
            ->where('saas_transactions.type', '=', 'debit')
            ->select(
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
            'index' => 'id',
            'label' => '#',
            'type' => 'integer',
            'sortable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'type',
            'label' => 'Tipo',
            'type' => 'string',
            'sortable' => true,
            'closure' => function ($row) {
                if ($row->type === 'credit') {
                    return '<span class="badge badge-round badge-success">Crédito</span>';
                }
                return '<span class="badge badge-round badge-danger">Débito</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'service_type',
            'label' => 'Serviço',
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'description',
            'label' => 'Descrição',
            'type' => 'string',
            'sortable' => false,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'amount',
            'label' => 'Valor',
            'type' => 'string',
            'sortable' => true,
            'closure' => fn($row) => 'R$ ' . number_format($row->amount, 2, ',', '.'),
        ]);

        $this->addColumn([
            'index' => 'balance_after',
            'label' => 'Saldo Após',
            'type' => 'string',
            'sortable' => false,
            'closure' => function ($row) {
                if ($row->balance_after === null)
                    return '—';
                return 'R$ ' . number_format($row->balance_after, 2, ',', '.');
            },
        ]);

        $this->addColumn([
            'index' => 'user_name',
            'label' => 'Usuário',
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'closure' => fn($row) => $row->user_name ?: '(Sistema)',
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => 'Data/Hora',
            'type' => 'datetime',
            'sortable' => true,
            'closure' => fn($row) => core()->formatDate($row->created_at, 'd/m/Y H:i'),
        ]);
    }

    public function prepareActions()
    {
    }
}
