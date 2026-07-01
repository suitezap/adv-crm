<?php

namespace SuiteZap\LawFirm\SaaS\DataGrids;

use Illuminate\Support\Facades\DB;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use Webkul\DataGrid\DataGrid;

/**
 * SaasOrdersDataGrid — Exibe o histórico de pedidos (intenções de compra)
 * da assinatura SaaS, estritamente isolado por tenant.
 *
 * Cada pedido é criado ANTES de chamar o gateway Asaas, permitindo
 * visibilidade completa do ciclo de vida: PENDING → PAID / EXPIRED / CANCELED.
 *
 * SEGURANÇA: Sempre filtra por tenant_id do tenant atual.
 */
class SaasOrdersDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    protected $sortColumn = 'id';

    protected $sortOrder = 'desc';

    public function prepareQueryBuilder()
    {
        $tenantId = MotherShipService::getTenantId();

        $queryBuilder = DB::table('saas_orders')
            ->leftJoin('users', 'saas_orders.user_id', '=', 'users.id')
            ->where('saas_orders.tenant_id', $tenantId)
            ->whereNotNull('saas_orders.tenant_id')
            ->select(
                'saas_orders.id',
                'saas_orders.type',
                'saas_orders.value',
                'saas_orders.status',
                'saas_orders.asaas_payment_id',
                'saas_orders.description',
                'users.name as user_name',
                'saas_orders.created_at'
            );

        $this->addFilter('id', 'saas_orders.id');
        $this->addFilter('type', 'saas_orders.type');
        $this->addFilter('status', 'saas_orders.status');
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
                if ($row->type === 'ai_credits') {
                    return '<span class="badge badge-round badge-primary">Créditos IA</span>';
                }

                return '<span class="badge badge-round badge-info">Assinatura</span>';
            },
        ]);

        $this->addColumn([
            'index'    => 'value',
            'label'    => 'Valor',
            'type'     => 'string',
            'sortable' => true,
            'closure'  => fn ($row) => 'R$ '.number_format($row->value, 2, ',', '.'),
        ]);

        $this->addColumn([
            'index'    => 'status',
            'label'    => 'Status',
            'type'     => 'string',
            'sortable' => true,
            'closure'  => function ($row) {
                return match ($row->status) {
                    'PAID'     => '<span class="badge badge-round badge-success">Pago</span>',
                    'PENDING'  => '<span class="badge badge-round badge-warning">Pendente</span>',
                    'EXPIRED'  => '<span class="badge badge-round badge-danger">Expirado</span>',
                    'CANCELED' => '<span class="badge badge-round badge-danger">Cancelado</span>',
                    default    => '<span class="badge badge-round badge-secondary">'.$row->status.'</span>',
                };
            },
        ]);

        $this->addColumn([
            'index'      => 'description',
            'label'      => 'Descrição',
            'type'       => 'string',
            'sortable'   => false,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'user_name',
            'label'      => 'Usuário',
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
            'closure'    => fn ($row) => $row->user_name ?: '(Sistema)',
        ]);

        $this->addColumn([
            'index'      => 'asaas_payment_id',
            'label'      => 'ID Asaas',
            'type'       => 'string',
            'sortable'   => false,
            'searchable' => true,
            'closure'    => fn ($row) => $row->asaas_payment_id ?: '—',
        ]);

        $this->addColumn([
            'index'    => 'created_at',
            'label'    => 'Data/Hora',
            'type'     => 'datetime',
            'sortable' => true,
            'closure'  => fn ($row) => core()->formatDate($row->created_at, 'd/m/Y H:i'),
        ]);
    }

    public function prepareActions() {}
}
