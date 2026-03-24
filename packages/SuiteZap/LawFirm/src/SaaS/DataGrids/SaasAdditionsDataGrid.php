<?php

namespace SuiteZap\LawFirm\SaaS\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

/**
 * SaasAdditionsDataGrid — Exibe o histórico de créditos adicionados pelo cliente.
 */
class SaasAdditionsDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';
    protected $sortColumn = 'id';
    protected $sortOrder = 'desc';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('saas_transactions')
            ->leftJoin('users', 'saas_transactions.user_id', '=', 'users.id')
            ->where('saas_transactions.type', '=', 'credit')
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
            'closure' => function () {
                return '<span class="badge badge-round badge-success">Crédito Adicionado</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'description',
            'label' => 'Descrição / Origem',
            'type' => 'string',
            'sortable' => false,
            'searchable' => true,
            'closure' => function($row) {
                return 'Recarga (Asaas ou Sistema)'; // Can optionally display $row->description
            }
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
            'label' => 'Saldo Final',
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
            'label' => 'Usuário / Responsável',
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
            'closure' => fn($row) => $row->user_name ?: '(Asaas/Sistema)',
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
