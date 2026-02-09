<?php

namespace SuiteZap\LawFirm\DataGrids;

use Webkul\Admin\DataGrids\Activity\ActivityDataGrid as CoreActivityDataGrid;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\User\Repositories\UserRepository;

class SafeActivityDataGrid extends CoreActivityDataGrid
{
    /**
     * Prepare columns.
     * OVERRIDE: Adicionando proteções contra dados inconsistentes
     * - Fix: Lead Title sem ID
     * - Fix: Comment nulo quebrando Vue JS
     */
    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'id',
            'label' => trans('admin::app.activities.index.datagrid.id'),
            'type' => 'integer',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'is_done',
            'label' => trans('admin::app.activities.index.datagrid.is_done'),
            'type' => 'string',
            'dropdown_options' => $this->getBooleanDropdownOptions('yes_no'),
            'searchable' => false,
            'closure' => fn($row) => view('admin::activities.datagrid.is-done', compact('row'))->render(),
        ]);

        $this->addColumn([
            'index' => 'title',
            'label' => trans('admin::app.activities.index.datagrid.title'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'created_by_id',
            'label' => trans('admin::app.activities.index.datagrid.created_by'),
            'type' => 'string',
            'searchable' => false,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'searchable_dropdown',
            'filterable_options' => [
                'repository' => UserRepository::class,
                'column' => [
                    'label' => 'name',
                    'value' => 'name',
                ],
            ],
            'closure' => function ($row) {
                if (empty($row->created_by_id)) {
                    return $row->created_by ?? 'N/A';
                }
                $route = urldecode(route('admin.settings.users.index', ['id[eq]' => $row->created_by_id]));

                return "<a class='text-brandColor hover:underline' href='" . $route . "'>" . $row->created_by . '</a>';
            },
        ]);

        // ✅ CRITICAL FIX: Ensure comment is never null to prevent Vue .length crash
        $this->addColumn([
            'index' => 'comment',
            'label' => trans('admin::app.activities.index.datagrid.comment'),
            'type' => 'string',
            'closure' => function ($row) {
                return $row->comment ?? '';
            },
        ]);

        $this->addColumn([
            'index' => 'lead_title',
            'label' => trans('admin::app.activities.index.datagrid.lead'),
            'type' => 'string',
            'searchable' => true,
            'sortable' => true,
            'filterable' => true,
            'filterable_type' => 'searchable_dropdown',
            'filterable_options' => [
                'repository' => LeadRepository::class,
                'column' => [
                    'label' => 'title',
                    'value' => 'title',
                ],
            ],
            'closure' => function ($row) {
                if (empty($row->lead_title) || empty($row->lead_id)) {
                    return "<span class='text-gray-800 dark:text-gray-300'>N/A</span>";
                }

                $route = urldecode(route('admin.leads.view', $row->lead_id));

                return "<a class='text-brandColor hover:underline' target='_blank' href='" . $route . "'>" . $row->lead_title . '</a>';
            },
        ]);

        $this->addColumn([
            'index' => 'type',
            'label' => trans('admin::app.activities.index.datagrid.type'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
            'closure' => fn($row) => trans('admin::app.activities.index.datagrid.' . $row->type),
        ]);

        $this->addColumn([
            'index' => 'schedule_from',
            'label' => trans('admin::app.activities.index.datagrid.schedule_from'),
            'type' => 'date',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
            'closure' => fn($row) => core()->formatDate($row->schedule_from),
        ]);

        $this->addColumn([
            'index' => 'schedule_to',
            'label' => trans('admin::app.activities.index.datagrid.schedule_to'),
            'type' => 'date',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
            'closure' => fn($row) => core()->formatDate($row->schedule_to),
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('admin::app.activities.index.datagrid.created_at'),
            'type' => 'date',
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
            'closure' => fn($row) => core()->formatDate($row->created_at),
        ]);
    }
}
