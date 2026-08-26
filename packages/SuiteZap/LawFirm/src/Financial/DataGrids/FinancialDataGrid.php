<?php

namespace SuiteZap\LawFirm\Financial\DataGrids;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class FinancialDataGrid extends DataGrid
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
    protected $sortColumn = 'data_vencimento';

    /**
     * Default sort order.
     *
     * @var string
     */
    protected $sortOrder = 'asc';

    /**
     * Prepare query builder.
     *
     * @return void
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('law_financials')
            ->leftJoin('processos', 'law_financials.processo_id', '=', 'processos.id')
            ->leftJoin('persons', 'processos.person_id', '=', 'persons.id')
            ->addSelect(
                'law_financials.id',
                'law_financials.processo_id',
                'processos.titulo as processo_titulo',
                'law_financials.tipo',
                'law_financials.nome',
                'law_financials.valor',
                'law_financials.valor as raw_valor',
                'law_financials.data_vencimento',
                'law_financials.data_vencimento as raw_data_vencimento',
                'law_financials.status',
                'law_financials.status as raw_status',
                'law_financials.payment_method',
                'persons.name as person_name',
                'persons.contact_numbers'
            );

        // Security / ACL Scope - Filter by User Permissions
        if ($userIds = bouncer()->getAuthorizedUserIds()) {
            $queryBuilder->whereIn('processos.user_id', $userIds);
        }

        $this->addFilter('id', 'law_financials.id');
        $this->addFilter('tipo', 'law_financials.tipo');
        $this->addFilter('nome', 'law_financials.nome');
        $this->addFilter('valor', 'law_financials.valor');
        $this->addFilter('data_vencimento', 'law_financials.data_vencimento');
        $this->addFilter('status', 'law_financials.status');
        $this->addFilter('payment_method', 'law_financials.payment_method');
        $this->addFilter('processo_titulo', 'processos.titulo');

        // Custom Sorting: Pendente (1) > Others (2) | Then Due Date Ascending
        $queryBuilder->orderByRaw("CASE WHEN law_financials.status = 'pendente' THEN 1 ELSE 2 END ASC");
        $queryBuilder->orderBy('law_financials.data_vencimento', 'asc');

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
            'label'      => 'ID',
            'type'       => 'integer',
            'sortable'   => true,
            'filterable' => true,
            'width'      => '50px',
        ]);

        $this->addColumn([
            'index'      => 'processo_titulo',
            'label'      => 'Processo',
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'closure'    => function ($row) {
                if ($row->processo_id) {
                    $url = route('admin.processos.show', $row->processo_id);

                    return '<a href="'.$url.'" class="text-blue-600 hover:underline font-medium">'.e($row->processo_titulo).'</a>';
                }

                return '-';
            },
        ]);

        $this->addColumn([
            'index'              => 'tipo',
            'label'              => 'Tipo',
            'type'               => 'string',
            'sortable'           => true,
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                ['label' => 'Receita', 'value' => 'receita'],
                ['label' => 'Despesa', 'value' => 'despesa'],
            ],
            'width'   => '100px',
            'closure' => function ($row) {
                if ($row->tipo === 'receita') {
                    return '<span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Receita</span>';
                }

                return '<span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Despesa</span>';
            },
        ]);

        $this->addColumn([
            'index'      => 'nome',
            'label'      => 'Descrição',
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
        ]);

        $this->addColumn([
            'index'      => 'valor',
            'label'      => 'Valor',
            'type'       => 'string',
            'sortable'   => true,
            'filterable' => true,
            'width'      => '120px',
            'closure'    => function ($row) {
                return 'R$ '.number_format($row->valor, 2, ',', '.');
            },
        ]);

        $this->addColumn([
            'index'      => 'data_vencimento',
            'label'      => 'Vencimento',
            'type'       => 'date',
            'sortable'   => true,
            'filterable' => true,
            'width'      => '120px',
            'closure'    => function ($row) {
                if (! $row->data_vencimento) {
                    return '-';
                }

                return Carbon::parse($row->data_vencimento)->format('d/m/Y');
            },
        ]);

        $this->addColumn([
            'index'              => 'payment_method',
            'label'              => 'Forma Pgto',
            'type'               => 'string',
            'sortable'           => true,
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                ['label' => 'PIX', 'value' => 'pix'],
                ['label' => 'Boleto', 'value' => 'boleto'],
                ['label' => 'Cartão', 'value' => 'cartao'],
                ['label' => 'Transferência', 'value' => 'transferencia'],
                ['label' => 'Dinheiro', 'value' => 'dinheiro'],
            ],
            'width'   => '110px',
            'closure' => function ($row) {
                $labels = [
                    'pix'           => ['PIX', 'bg-emerald-100 text-emerald-800'],
                    'boleto'        => ['Boleto', 'bg-blue-100 text-blue-800'],
                    'cartao'        => ['Cartão', 'bg-violet-100 text-violet-800'],
                    'transferencia' => ['Transf.', 'bg-amber-100 text-amber-800'],
                    'dinheiro'      => ['Dinheiro', 'bg-gray-100 text-gray-700'],
                ];
                $pm = $row->payment_method ?? '';
                if (isset($labels[$pm])) {
                    return '<span class="px-2 py-0.5 rounded-full text-xs font-semibold '.$labels[$pm][1].'">'.$labels[$pm][0].'</span>';
                }

                return '<span class="text-gray-400 text-xs">—</span>';
            },
        ]);

        $this->addColumn([
            'index'              => 'status',
            'label'              => 'Status',
            'type'               => 'string',
            'sortable'           => true,
            'filterable'         => true,
            'filterable_type'    => 'dropdown',
            'filterable_options' => [
                ['label' => 'Pendente', 'value' => 'pendente'],
                ['label' => 'Pago', 'value' => 'pago'],
                ['label' => 'Cancelado', 'value' => 'cancelado'],
            ],
            'width'   => '100px',
            'closure' => function ($row) {
                $colors = [
                    'pendente'  => 'text-orange-600 font-medium',
                    'pago'      => 'text-green-600 font-medium',
                    'cancelado' => 'text-gray-400 line-through',
                ];
                $class = $colors[$row->status] ?? 'text-gray-600';

                return '<span class="'.$class.'">'.ucfirst($row->status).'</span>';
            },
        ]);
        $this->addColumn([
            'index'      => 'quick_pay',
            'label'      => 'Ações',
            'type'       => 'string',
            'sortable'   => false,
            'filterable' => false,
            'width'      => '50px',
            'closure'    => function ($row) {
                // Use raw_status to bypass the HTML-transformed 'status' column
                if (strtolower(trim($row->raw_status ?? '')) == 'pendente') {
                    return '<button type="button" onclick="openQuickPay('.$row->id.')" 
                                    class="px-2 py-1 rounded bg-green-600 text-white text-xs font-bold hover:bg-green-700" 
                                    title="Baixar">
                                BAIXAR
                            </button>';
                }

                return '<span class="text-xs text-gray-400">('.($row->raw_status ?? '-').')</span>';
            },
        ]);

        // Coluna: Cobrança via WhatsApp
        $this->addColumn([
            'index'      => 'cobrar',
            'label'      => 'Cobrança',
            'type'       => 'string',
            'sortable'   => false,
            'filterable' => false,
            'width'      => '50px',
            'closure'    => function ($row) {
                // Se pago, não mostra nada
                if (strtolower(trim($row->raw_status ?? '')) == 'pago') {
                    return '';
                }

                // Tenta extrair o telefone
                $phone = null;
                if (! empty($row->contact_numbers)) {
                    $contactData = json_decode($row->contact_numbers, true);
                    if (is_array($contactData)) {
                        foreach ($contactData as $contact) {
                            if (! empty($contact['value'])) {
                                $phone = $contact['value'];
                                break;
                            }
                        }
                    } else {
                        // Caso seja string direta
                        $phone = $row->contact_numbers;
                    }
                }

                // Sanitização mantendo apenas números
                $cleanPhone = preg_replace('/\D/', '', $phone ?? '');

                // Lógica do código do país (Adiciona 55 se tiver 10 ou 11 dígitos, Brasil)
                if (strlen($cleanPhone) >= 10 && strlen($cleanPhone) <= 11) {
                    $cleanPhone = '55'.$cleanPhone;
                }

                // Se não tiver telefone válido
                if (empty($cleanPhone)) {
                    return '<button type="button" disabled class="px-2 py-1 rounded bg-gray-300 text-white text-xs font-bold cursor-not-allowed" title="Sem telefone cadastrado">
                                <span class="icon-message"></span> ZAP
                            </button>';
                }

                // Lógica da mensagem
                $valor = number_format((float) ($row->raw_valor ?? 0), 2, ',', '.');
                $descricao = $row->nome;
                $nomeCliente = explode(' ', trim($row->person_name ?? 'Cliente'))[0];

                // Parse da data de vencimento com tratamento de formato
                $dataVencimento = null;
                $rawDate = $row->raw_data_vencimento ?? $row->data_vencimento;
                if ($rawDate) {
                    // Tenta formato ISO (Y-m-d) primeiro, depois BR (d/m/Y)
                    try {
                        $dataVencimento = Carbon::parse($rawDate);
                    } catch (\Exception $e) {
                        try {
                            $dataVencimento = Carbon::createFromFormat('d/m/Y', $rawDate);
                        } catch (\Exception $e2) {
                            $dataVencimento = null;
                        }
                    }
                }
                $hoje = Carbon::now()->startOfDay();

                // O frontend cuidará da confirmação e do disparo Ajax
                return '<button type="button" onclick="sendWhatsappBilling('.$row->id.')" class="px-2 py-1 rounded bg-green-500 text-white text-xs font-bold hover:bg-green-600 inline-block text-center" title="Enviar Cobrança">
                            <span class="icon-message"></span> ZAP
                        </button>';
            },
        ]);

        // Coluna: Recibo PDF (apenas para Pago)
        $this->addColumn([
            'index'      => 'receipt_action',
            'label'      => 'Recibo',
            'type'       => 'string',
            'sortable'   => false,
            'filterable' => false,
            'width'      => '50px',
            'closure'    => function ($row) {
                $status = strtolower(trim($row->raw_status ?? ''));

                if ($status == 'pago') {
                    $url = route('admin.lawfirm.financial.receipt', $row->id);

                    return '<a href="'.$url.'" target="_blank" 
                                class="px-2 py-1 rounded text-xs font-bold inline-block text-center"
                                style="background-color: #2563eb !important; color: white !important;"
                                title="Gerar Recibo PDF">
                                RECIBO
                            </a>';
                }

                return '<span class="text-gray-300 text-xs font-bold cursor-not-allowed" title="Disponível apenas para status Pago">
                            RECIBO
                        </span>';
            },
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        // View action - goes to the process show page
        $this->addAction([
            'icon'   => 'icon-eye',
            'title'  => 'Ver Processo',
            'method' => 'GET',
            'url'    => function ($row) {
                return route('admin.processos.show', $row->processo_id);
            },
        ]);
    }
}
