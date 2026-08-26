<?php

namespace SuiteZap\LawFirm\TenantFinance\DataGrids;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

/**
 * TenantInvoiceDataGrid — Cobranças emitidas pelo escritório para seus clientes.
 */
class TenantInvoiceDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    protected $sortColumn = 'id';

    protected $sortOrder = 'desc';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('tenant_invoices')
            ->leftJoin('tenant_asaas_customers', 'tenant_invoices.tenant_asaas_customer_id', '=', 'tenant_asaas_customers.id')
            ->leftJoin('processos', 'tenant_invoices.processo_id', '=', 'processos.id')
            ->select(
                'tenant_invoices.id',
                'tenant_asaas_customers.name as customer_name',
                'processos.titulo as processo_titulo',
                'tenant_invoices.type',
                'tenant_invoices.description',
                'tenant_invoices.value',
                'tenant_invoices.billing_type',
                'tenant_invoices.status',
                'tenant_invoices.due_date',
                'tenant_invoices.payment_date',
                'tenant_invoices.invoice_url',
                'tenant_invoices.created_at'
            );

        // Security / ACL Scope - Filter by User Permissions
        if ($userIds = bouncer()->getAuthorizedUserIds()) {
            $queryBuilder->whereIn('processos.user_id', $userIds);
        }

        $this->addFilter('id', 'tenant_invoices.id');
        $this->addFilter('status', 'tenant_invoices.status');
        $this->addFilter('customer_name', 'tenant_asaas_customers.name');
        $this->addFilter('billing_type', 'tenant_invoices.billing_type');
        $this->addFilter('processo_titulo', 'processos.titulo');

        $this->setQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'    => 'id',
            'label'    => '#',
            'type'     => 'integer',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'      => 'customer_name',
            'label'      => 'Cliente',
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'processo_titulo',
            'label'      => 'Processo',
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
            'closure'    => fn ($row) => $row->processo_titulo ?: '—',
        ]);

        $this->addColumn([
            'index'      => 'description',
            'label'      => 'Descrição',
            'type'       => 'string',
            'sortable'   => false,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'    => 'value',
            'label'    => 'Valor',
            'type'     => 'string',
            'sortable' => true,
            'closure'  => fn ($row) => 'R$ '.number_format($row->value, 2, ',', '.'),
        ]);

        $this->addColumn([
            'index'    => 'billing_type',
            'label'    => 'Forma',
            'type'     => 'string',
            'sortable' => true,
            'closure'  => function ($row) {
                return match ($row->billing_type) {
                    'PIX'         => '<span class="badge badge-round badge-success">PIX</span>',
                    'BOLETO'      => '<span class="badge badge-round badge-info">Boleto</span>',
                    'CREDIT_CARD' => '<span class="badge badge-round badge-primary">Cartão</span>',
                    default       => '<span class="badge badge-round badge-secondary">'.$row->billing_type.'</span>',
                };
            },
        ]);

        $this->addColumn([
            'index'    => 'status',
            'label'    => 'Status',
            'type'     => 'string',
            'sortable' => true,
            'closure'  => function ($row) {
                return match ($row->status) {
                    'RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH' => '<span class="badge badge-round badge-success">Pago</span>',
                    'PENDING'                                   => '<span class="badge badge-round badge-warning">Pendente</span>',
                    'OVERDUE'                                   => '<span class="badge badge-round badge-danger">Vencida</span>',
                    'CANCELED'                                  => '<span class="badge badge-round badge-dark">Cancelada</span>',
                    'REFUNDED'                                  => '<span class="badge badge-round badge-info">Estornada</span>',
                    default                                     => '<span class="badge badge-round badge-secondary">'.$row->status.'</span>',
                };
            },
        ]);

        $this->addColumn([
            'index'    => 'due_date',
            'label'    => 'Vencimento',
            'type'     => 'date',
            'sortable' => true,
            'closure'  => fn ($row) => $row->due_date ? Carbon::parse($row->due_date)->format('d/m/Y') : '—',
        ]);

        $this->addColumn([
            'index'    => 'created_at',
            'label'    => 'Criada em',
            'type'     => 'datetime',
            'sortable' => true,
            'closure'  => fn ($row) => core()->formatDate($row->created_at, 'd/m/Y H:i'),
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'icon'   => 'icon-eye',
            'title'  => 'Visualizar',
            'method' => 'GET',
            'url'    => function ($row) {
                return route('admin.lawfirm.tenant_finance.show', $row->id);
            },
        ]);
    }
}
