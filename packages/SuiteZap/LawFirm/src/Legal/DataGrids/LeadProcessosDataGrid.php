<?php

namespace SuiteZap\LawFirm\Legal\DataGrids;

class LeadProcessosDataGrid extends ProcessoDataGrid
{
    /**
     * Prepare query builder.
     *
     * @return void
     */
    public function prepareQueryBuilder()
    {
        parent::prepareQueryBuilder();

        $queryBuilder = $this->queryBuilder;

        // Get lead_id from the route parameter
        $leadId = request()->route()->parameter('id');

        if ($leadId) {
            $queryBuilder->where('processos.lead_id', $leadId);
        }

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
            'index'      => 'id',
            'label'      => trans('lawfirm::app.processos.datagrid.id'),
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'titulo',
            'label'      => trans('lawfirm::app.processos.datagrid.titulo'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'area_direito',
            'label'      => trans('lawfirm::app.processos.form.area'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'data_audiencia',
            'label'      => trans('lawfirm::app.processos.form.data_audiencia'),
            'type'       => 'datetime',
            'sortable'   => true,
            'filterable' => true,
            'closure'    => function ($row) {
                if (! $row->data_audiencia) {
                    return '-';
                }

                return \Carbon\Carbon::parse($row->data_audiencia)->format('d/m/Y H:i');
            },
        ]);

        $this->addColumn([
            'index'      => 'numero_cnj',
            'label'      => trans('lawfirm::app.processos.datagrid.cnj'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('lawfirm::app.processos.datagrid.status'),
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
        ]);

        // Removed person_name intentionally
    }
}
