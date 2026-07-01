<?php

namespace SuiteZap\LawFirm\Escavador\DataGrids;

use Illuminate\Support\Facades\DB;
use SuiteZap\LawFirm\SaaS\Services\SuiteCoinService;
use Webkul\DataGrid\DataGrid;

/**
 * EscavadorHistoryDataGrid — Exibe o histórico de requisições aos serviços jurídicos
 * do Escavador, incluindo status, custo e tipo de serviço.
 */
class EscavadorHistoryDataGrid extends DataGrid
{
    protected $primaryColumn = 'id';

    protected $sortColumn = 'id';

    protected $sortOrder = 'desc';

    /**
     * Labels de exibição para os tipos de serviço.
     */
    private const TYPE_LABELS = [
        'CAPA_PROCESSO'              => 'Capa do Processo (V2)',
        'PDF_DIARIO'                 => 'PDF do Diário (V1)',
        'BUSCA_TERMO'                => 'Busca por Termo (V1)',
        'RESUMO_IA'                  => 'Resumo Inteligente IA',
        'BUSCA_JURIS'                => 'Busca Jurisprudência',
        'BUSCA_DIARIO'               => 'Busca Diário Oficial',
        'INFO_INSTITUICAO'           => 'Info Instituição',
        'INFO_PESSOA'                => 'Info Pessoa',
        'BUSCA_OAB_PAGA'             => 'Busca por OAB',
        'ATUALIZAR_PROCESSO'         => 'Atualizar Processo',
        'BAIXAR_AUTOS'               => 'Baixar Autos',
        'DOCUMENTOS_PUBLICOS'        => 'Docs. Públicos',
        'ENVOLVIDOS_PROCESSO'        => 'Envolvidos',
        'MOVIMENTACOES_PROCESSO'     => 'Movimentações',
        'RESUMO_ADVOGADO_OAB'        => 'Resumo Advogado',
        'RESUMO_ENVOLVIDO'           => 'Resumo Envolvido',
        'PROCESSOS_ENVOLVIDO_CPF'    => 'Processos por CPF',
        'ATUALIZACAO_PROCESSO_DOCS'  => 'Atualiz. (docs)',
        'ATUALIZACAO_PROCESSO_AUTOS' => 'Atualiz. (autos)',
        'ATUALIZACAO_PROCESSO_PUB'   => 'Atualiz. (público)',
    ];

    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('escavador_requests')
            ->leftJoin('processos', 'escavador_requests.processo_id', '=', 'processos.id')
            ->select(
                'escavador_requests.id',
                'escavador_requests.endpoint_type',
                'escavador_requests.status',
                'escavador_requests.cost',
                'escavador_requests.external_id',
                'processos.numero_cnj as processo_numero',
                'escavador_requests.created_at'
            );

        $this->addFilter('id', 'escavador_requests.id');
        $this->addFilter('status', 'escavador_requests.status');
        $this->addFilter('endpoint_type', 'escavador_requests.endpoint_type');

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
            'index'      => 'endpoint_type',
            'label'      => 'Serviço',
            'type'       => 'string',
            'sortable'   => true,
            'searchable' => true,
            'closure'    => fn ($row) => self::TYPE_LABELS[$row->endpoint_type] ?? $row->endpoint_type,
        ]);

        $this->addColumn([
            'index'      => 'processo_numero',
            'label'      => 'Processo',
            'type'       => 'string',
            'sortable'   => false,
            'searchable' => false,
            'closure'    => fn ($row) => $row->processo_numero ?: '—',
        ]);

        $this->addColumn([
            'index'    => 'status',
            'label'    => 'Status',
            'type'     => 'string',
            'sortable' => true,
            'closure'  => function ($row) {
                return match ($row->status) {
                    'completed' => '<span class="badge badge-round badge-success">Concluído</span>',
                    'pending'   => '<span class="badge badge-round badge-warning">Aguardando</span>',
                    'failed'    => '<span class="badge badge-round badge-danger">Falhou</span>',
                    default     => '<span class="badge badge-round badge-secondary">'.ucfirst($row->status).'</span>',
                };
            },
        ]);

        $this->addColumn([
            'index'    => 'cost',
            'label'    => 'Custo',
            'type'     => 'string',
            'sortable' => true,
            'closure'  => fn ($row) => SuiteCoinService::formatFromBrl((float) $row->cost),
        ]);

        $this->addColumn([
            'index'    => 'created_at',
            'label'    => 'Data/Hora',
            'type'     => 'datetime',
            'sortable' => true,
            'closure'  => fn ($row) => core()->formatDate($row->created_at, 'd/m/Y H:i'),
        ]);
    }

    public function prepareActions()
    {
        $this->addAction([
            'icon'   => 'icon-eye',
            'title'  => 'Visualizar Resultado',
            'method' => 'GET',
            'url'    => function ($row) {
                return route('lawfirm.escavador.history.show', $row->id);
            },
        ]);
    }
}
