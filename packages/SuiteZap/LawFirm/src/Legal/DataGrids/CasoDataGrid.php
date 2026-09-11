<?php

namespace SuiteZap\LawFirm\Legal\DataGrids;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class CasoDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('law_casos')
            ->leftJoin('users', 'law_casos.user_id', '=', 'users.id')
            ->leftJoin('persons', 'law_casos.person_id', '=', 'persons.id')
            ->leftJoin('organizations', 'law_casos.organization_id', '=', 'organizations.id')
            ->select(
                'law_casos.id',
                'law_casos.titulo',
                'law_casos.area',
                'law_casos.status',
                'law_casos.prioridade',
                'law_casos.created_at',
                'users.name as responsavel_nome',
                DB::raw('COALESCE(persons.name, organizations.name) as cliente_nome'),
                DB::raw('(SELECT COUNT(*) FROM processos WHERE processos.caso_id = law_casos.id) as processos_count')
            );

        // Scoping by authorized user IDs (ACL view_permission)
        $authorizedIds = bouncer()->getAuthorizedUserIds();
        if ($authorizedIds && count($authorizedIds) > 0) {
            $queryBuilder->whereIn('law_casos.user_id', $authorizedIds);
        }

        $this->addFilter('id', 'law_casos.id');
        $this->addFilter('titulo', 'law_casos.titulo');
        $this->addFilter('area', 'law_casos.area');
        $this->addFilter('status', 'law_casos.status');
        $this->addFilter('responsavel_nome', 'users.name');
        $this->addFilter('created_at', 'law_casos.created_at');

        return $queryBuilder;
    }

    /**
     * Prepare columns.
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => 'ID',
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'titulo',
            'label'      => 'Título',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'area',
            'label'      => 'Área',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                if (! $row->area) {
                    return '—';
                }

                $areaColors = [
                    'Administrativo' => '#A9CCE3', 'Ambiental' => '#A3E4D7', 'Bancário' => '#D4E6B5',
                    'Consumidor'     => '#F8CBA6', 'Cível' => '#C7D3DD', 'Digital / LGPD' => '#C5CAE9',
                    'Empresarial'    => '#D7BDE2', 'Família' => '#F5B7B1', 'Imobiliário' => '#E8D8C3',
                    'Penal'          => '#F4A7A7', 'Previdenciário' => '#A8D5BA', 'Trabalhista' => '#A7C7E7',
                    'Tributário'     => '#F9E79F',
                ];

                $bg = $areaColors[$row->area] ?? null;

                if ($bg) {
                    return '<span class="px-2 py-1 rounded-full text-xs font-semibold" style="background-color: '.$bg.'; color: #333;">'.htmlspecialchars($row->area).'</span>';
                }

                return htmlspecialchars($row->area);
            },
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => 'Status',
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                $badge = 'bg-gray-100 text-gray-600';
                $label = $row->status ?: '—';

                return '<span class="px-2 py-1 rounded-full text-xs font-semibold '.$badge.'">'.htmlspecialchars($label).'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'prioridade',
            'label'      => 'Prioridade',
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {
                if (! $row->prioridade) {
                    return '—';
                }

                $normalized = strtolower(str_replace(['í', 'é', 'Í', 'É'], ['i', 'e', 'i', 'e'], $row->prioridade));

                $color = match ($normalized) {
                    'critica' => '#D96B6B',
                    'alta'    => '#E89B4D',
                    'media'   => '#E6C15A',
                    'baixa'   => '#7BC67B',
                    default   => '#6B7280',
                };

                return '<span class="px-2 py-1 rounded-full text-xs font-semibold text-white" style="background-color: '.$color.';">'.ucfirst($row->prioridade).'</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'cliente_nome',
            'label'      => 'Cliente',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'responsavel_nome',
            'label'      => 'Responsável',
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'processos_count',
            'label'      => 'Processos',
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => 'Criado em',
            'type'       => 'date',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row) => core()->formatDate($row->created_at),
        ]);
    }

    /**
     * Prepare actions.
     */
    public function prepareActions()
    {
        $this->addAction([
            'index'  => 'view',
            'icon'   => 'icon-eye',
            'title'  => 'Ver Caso',
            'method' => 'GET',
            'url'    => function ($row) {
                return route('admin.lawfirm.casos.show', $row->id);
            },
        ]);

        $this->addAction([
            'index'  => 'edit',
            'icon'   => 'icon-edit',
            'title'  => 'Editar',
            'method' => 'GET',
            'url'    => function ($row) {
                return route('admin.lawfirm.casos.edit', $row->id);
            },
        ]);

        $this->addAction([
            'index'  => 'delete',
            'icon'   => 'icon-delete',
            'title'  => 'Excluir',
            'method' => 'DELETE',
            'url'    => function ($row) {
                return route('admin.lawfirm.casos.destroy', $row->id);
            },
        ]);
    }

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions()
    {
        $this->addMassAction([
            'icon'   => 'icon-delete',
            'title'  => 'Excluir',
            'method' => 'POST',
            'url'    => route('admin.lawfirm.casos.mass_delete'),
        ]);
    }
}
