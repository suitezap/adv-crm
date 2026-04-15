<?php

namespace SuiteZap\LawFirm\AI\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class AssistantHistoryDataGrid extends DataGrid
{
    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'id';

    /**
     * Default sort column.
     *
     * @var string
     */
    protected $sortColumn = 'history_id';

    /**
     * Default sort order.
     *
     * @var string
     */
    protected $sortOrder = 'desc';

    /**
     * Prepare query builder.
     *
     * @return void
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('lawfirm_assistant_history')
            ->leftJoin('users', 'lawfirm_assistant_history.user_id', '=', 'users.id')
            ->leftJoin('leads', 'lawfirm_assistant_history.lead_id', '=', 'leads.id')
            ->select(
                'lawfirm_assistant_history.id as history_id',
                'lawfirm_assistant_history.template_id',
                'users.name as user_name',
                'lawfirm_assistant_history.status',
                'lawfirm_assistant_history.execution_mode',
                'lawfirm_assistant_history.lead_id',
                'leads.title as lead_title',
                'lawfirm_assistant_history.created_at'
            );

        $this->addFilter('history_id', 'lawfirm_assistant_history.id');
        $this->addFilter('user_name', 'users.name');
        $this->addFilter('template_id', 'lawfirm_assistant_history.template_id');
        $this->addFilter('status', 'lawfirm_assistant_history.status');
        $this->addFilter('execution_mode', 'lawfirm_assistant_history.execution_mode');
        $this->addFilter('lead_id', 'lawfirm_assistant_history.lead_id');

        $this->setQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'history_id',
            'label' => 'ID',
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'template_name',
            'label' => 'Assistente',
            'type' => 'string',
            'sortable' => false,
            'searchable' => false,
            'closure' => function ($row) {
                $template = \SuiteZap\LawFirm\AI\Models\AssistantTemplate::find($row->template_id);
                return $template ? $template->title : 'Desconhecido';
            }
        ]);

        $this->addColumn([
            'index' => 'user_name',
            'label' => 'Usuário',
            'type' => 'string',
            'sortable' => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index' => 'origem',
            'label' => 'Origem',
            'type' => 'string',
            'sortable' => false,
            'searchable' => false,
            'closure' => function ($row) {
                if ($row->lead_id) {
                    $leadUrl  = route('admin.leads.view', $row->lead_id);
                    $leadTitle = e($row->lead_title ?? 'Lead #' . $row->lead_id);
                    return '<a href="' . $leadUrl . '" title="Abrir Lead" style="text-decoration:none;">'
                        . '<span class="badge badge-round" style="background:#7B2CBF;color:#fff;font-size:11px;white-space:nowrap;">'
                        . '🔗 Lead: ' . $leadTitle
                        . '</span></a>';
                }

                return '<span class="badge badge-round badge-secondary" style="font-size:11px;">Manual</span>';
            }
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => 'Status',
            'type' => 'string',
            'sortable' => true,
            'closure' => function ($row) {
                switch ($row->status) {
                    case 'completed':
                        return '<span class="badge badge-round badge-success">Concluído</span>';
                    case 'queued':
                    case 'processing':
                        return '<span class="badge badge-round badge-warning">Processando...</span>';
                    case 'error':
                    case 'failed':
                        return '<span class="badge badge-round badge-danger">Erro</span>';
                    default:
                        return '<span class="badge badge-round badge-secondary">' . ucfirst($row->status) . '</span>';
                }
            }
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => 'Data/Hora Execução',
            'type' => 'datetime',
            'sortable' => true,
            'searchable' => false,
            'closure' => function ($row) {
                return core()->formatDate($row->created_at, 'd/m/Y H:i');
            }
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        $this->addAction([
            'icon' => 'icon-eye',
            'title' => 'Visualizar Resultado',
            'method' => 'GET',
            'url' => function ($row) {
                return route('lawfirm.assistants.history.show', $row->history_id);
            },
        ]);
    }
}
