<?php

namespace SuiteZap\LawFirm\Escavador\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class EscavadorMonitoramentoDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    protected $sortColumn = 'id';

    protected $sortOrder = 'desc';

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('escavador_monitoramentos')
            ->select(
                'id',
                'external_id',
                'type',
                'query_value',
                'frequency',
                'notify_whatsapp',
                'created_at'
            );

        $this->addFilter('id', 'id');
        $this->addFilter('type', 'type');
        $this->addFilter('query_value', 'query_value');
        $this->addFilter('frequency', 'frequency');
        $this->addFilter('notify_whatsapp', 'notify_whatsapp');

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
            'index'      => 'type',
            'label'      => 'Tipo',
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
            'closure'    => function ($row) {
                return ucfirst($row->type);
            },
        ]);

        $this->addColumn([
            'index'      => 'query_value',
            'label'      => 'Termo / Processo Monitorado',
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
        ]);

        $this->addColumn([
            'index'      => 'frequency',
            'label'      => 'Frequência',
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
            'closure'    => function ($row) {
                $freq = $row->frequency ? ucfirst(strtolower($row->frequency)) : 'N/A';

                return '<span class="badge badge-round badge-secondary">'.$freq.'</span>';
            },
        ]);

        $this->addColumn([
            'index'    => 'notify_whatsapp',
            'label'    => 'Aler. WhatsApp',
            'type'     => 'boolean',
            'sortable' => true,
            'closure'  => function ($row) {
                $checked = $row->notify_whatsapp ? 'checked' : '';
                $url = route('lawfirm.escavador.monitoramentos.toggle_whatsapp', $row->id);

                return '<label class="switch">
                            <input type="checkbox" '.$checked.' 
                                   onchange="toggleWhatsappNotification('.$row->id.', this, \''.$url.'\')">
                            <span class="slider round"></span>
                        </label>';
            },
        ]);

        $this->addColumn([
            'index'    => 'created_at',
            'label'    => 'Criado Em',
            'type'     => 'datetime',
            'sortable' => true,
            'closure'  => fn ($row) => core()->formatDate($row->created_at, 'd/m/Y H:i'),
        ]);
    }

    public function prepareActions()
    {
        // Add if needed
    }
}
