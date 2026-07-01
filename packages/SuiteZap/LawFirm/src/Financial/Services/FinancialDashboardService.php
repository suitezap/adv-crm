<?php

namespace SuiteZap\LawFirm\Financial\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialDashboardService
{
    /**
     * Retorna todas as métricas do dashboard consolidadas.
     */
    public function getAllMetrics(?string $startDate = null, ?string $endDate = null): array
    {
        $kpis = $this->getKpis($startDate, $endDate);

        return [
            'totalReceitas'   => $kpis['totalReceitas'],
            'totalDespesas'   => $kpis['totalDespesas'],
            'saldoLiquido'    => $kpis['saldoLiquido'],
            'margemPercent'   => $kpis['margemPercent'],
            'pendenteReceber' => $this->getPendenteReceber($startDate, $endDate),
            'collectionRate'  => $this->getCollectionRate($startDate, $endDate),
            'dso'             => $this->getDso($startDate, $endDate),
            'aging'           => $this->getAgingList(),
        ];
    }

    /**
     * Retorna Query Base com filtros de Segurança (ACL) e Filtros Manuais.
     */
    private function getBaseQuery()
    {
        // Usa o Model Financial para aproveitar escopos e conexões do Eloquent
        $query = \SuiteZap\LawFirm\Financial\Models\Financial::query()
            ->join('processos', 'law_financials.processo_id', '=', 'processos.id')
            ->select('law_financials.*');

        // Tenta pegar o usuário de ambos os guards possíveis (admin é o padrão Krayin, user é o fallback)
        $user = auth()->guard('user')->user() ?? auth()->guard('admin')->user();
        $responsibleId = request('responsible_id');

        // 1. Aplica Segurança (ACL) nativa do Krayin (Bouncer)
        $userIds = bouncer()->getAuthorizedUserIds();

        if (is_array($userIds)) {
            // Se array, permissão é individual ou grupo (scope restrito)
            $query->whereIn('processos.user_id', $userIds);
        } else {
            // Se for null, é escopo global/admin (vê tudo).
            // 2. Aplica Filtro Manual de Responsável apenas se for admin/global
            if ($responsibleId && $responsibleId !== '') {
                $query->where('processos.user_id', $responsibleId);
            }
        }

        return $query;
    }

    /**
     * Calcula KPIs gerais: Total Receita, Total Despesa, Saldo Líquido, Margem %.
     */
    public function getKpis(?string $startDate = null, ?string $endDate = null): array
    {
        $query = $this->getBaseQuery();

        if ($startDate) {
            $query->where('law_financials.data_vencimento', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('law_financials.data_vencimento', '<=', $endDate);
        }

        $totalReceitas = (clone $query)->where('law_financials.tipo', 'receita')->sum('law_financials.valor') ?? 0;
        $totalDespesas = (clone $query)->where('law_financials.tipo', 'despesa')->sum('law_financials.valor') ?? 0;
        $saldoLiquido = $totalReceitas - $totalDespesas;

        // Margem % = (Saldo / Receitas) * 100 - Proteção contra divisão por zero
        $margemPercent = $totalReceitas > 0
            ? round(($saldoLiquido / $totalReceitas) * 100, 2)
            : 0;

        return [
            'totalReceitas' => $totalReceitas,
            'totalDespesas' => $totalDespesas,
            'saldoLiquido'  => $saldoLiquido,
            'margemPercent' => $margemPercent,
        ];
    }

    /**
     * Calcula Collection Rate: (Recebido / Faturado) * 100.
     * Considera apenas receitas que têm issued_at preenchido.
     */
    public function getCollectionRate(?string $startDate = null, ?string $endDate = null): float
    {
        $query = $this->getBaseQuery()
            ->where('law_financials.tipo', 'receita')
            ->whereNotNull('law_financials.issued_at');

        if ($startDate) {
            $query->where('law_financials.issued_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('law_financials.issued_at', '<=', $endDate);
        }

        $totalFaturado = (clone $query)->sum('law_financials.valor') ?? 0;
        $totalRecebido = (clone $query)->where('law_financials.status', 'pago')->sum('law_financials.valor') ?? 0;

        // Proteção contra divisão por zero
        if ($totalFaturado <= 0) {
            return 0;
        }

        return round(($totalRecebido / $totalFaturado) * 100, 2);
    }

    /**
     * Calcula DSO (Days Sales Outstanding): Média de dias entre issued_at e payment_date.
     * Considera apenas receitas pagas com ambas as datas preenchidas.
     */
    public function getDso(?string $startDate = null, ?string $endDate = null): float
    {
        $query = $this->getBaseQuery()
            ->where('law_financials.tipo', 'receita')
            ->where('law_financials.status', 'pago')
            ->whereNotNull('law_financials.payment_date')
            // Aceita registros com issued_at OU data_vencimento (fallback)
            ->where(function ($q) {
                $q->whereNotNull('law_financials.issued_at')
                    ->orWhereNotNull('law_financials.data_vencimento');
            });

        if ($startDate) {
            $query->where(DB::raw('COALESCE(law_financials.issued_at, law_financials.data_vencimento)'), '>=', $startDate);
        }
        if ($endDate) {
            $query->where(DB::raw('COALESCE(law_financials.issued_at, law_financials.data_vencimento)'), '<=', $endDate);
        }

        $records = $query->select(
            'law_financials.issued_at',
            'law_financials.data_vencimento',
            'law_financials.payment_date'
        )->get();

        if ($records->isEmpty()) {
            return 0;
        }

        $totalDays = 0;
        $count = 0;

        foreach ($records as $record) {
            // Data Inicial: Usa issued_at, se nulo usa data_vencimento (due_date)
            $start = $record->issued_at ?? $record->data_vencimento;

            if (! $start) {
                continue;
            } // Should be covered by query, but safe check

            $startDateObj = Carbon::parse($start);
            $paymentDateObj = Carbon::parse($record->payment_date);

            // false = não absoluto, permite negativos para validação
            $days = $startDateObj->diffInDays($paymentDateObj, false);

            // Filtro de Sanidade: Ignora erros de data (negativos) ou outliers (> 365 anos)
            if ($days < 0 || $days > 365) {
                continue;
            }

            $totalDays += $days;
            $count++;
        }

        // Proteção contra divisão por zero
        if ($count <= 0) {
            return 0;
        }

        return round($totalDays / $count, 1);
    }

    /**
     * Retorna Aging List: Contas a receber vencidas agrupadas em buckets.
     * Buckets: 0-30 dias, 31-60 dias, 61-90 dias, >90 dias.
     * Baseado em snapshot da data atual.
     */
    public function getAgingList(): array
    {
        $today = Carbon::today();

        $aging = [
            '0_30'    => 0,
            '31_60'   => 0,
            '61_90'   => 0,
            'over_90' => 0,
        ];

        // Busca receitas pendentes com vencimento no passado (vencidas)
        $overdueRecords = $this->getBaseQuery()
            ->where('law_financials.tipo', 'receita')
            ->where('law_financials.status', 'pendente')
            ->where('law_financials.data_vencimento', '<', $today)
            ->select('law_financials.data_vencimento', 'law_financials.valor')
            ->get();

        foreach ($overdueRecords as $record) {
            $vencimento = Carbon::parse($record->data_vencimento);
            $daysOverdue = $vencimento->diffInDays($today);
            $valor = (float) $record->valor;

            if ($daysOverdue <= 30) {
                $aging['0_30'] += $valor;
            } elseif ($daysOverdue <= 60) {
                $aging['31_60'] += $valor;
            } elseif ($daysOverdue <= 90) {
                $aging['61_90'] += $valor;
            } else {
                $aging['over_90'] += $valor;
            }
        }

        return $aging;
    }

    /**
     * Retorna dados para o gráfico de Receitas vs Despesas (últimos 6 meses).
     * Respeita ACL e Joins.
     */
    public function getMonthlyTrend(): array
    {
        return $this->getBaseQuery()
            ->selectRaw("
                DATE_FORMAT(law_financials.data_vencimento, '%Y-%m') as month,
                SUM(CASE WHEN law_financials.tipo = 'receita' AND law_financials.status != 'cancelado' THEN law_financials.valor ELSE 0 END) as receitas,
                SUM(CASE WHEN law_financials.tipo = 'despesa' AND law_financials.status != 'cancelado' THEN law_financials.valor ELSE 0 END) as despesas
            ")
            ->where('law_financials.data_vencimento', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->groupByRaw("DATE_FORMAT(law_financials.data_vencimento, '%Y-%m')")
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month'    => Carbon::createFromFormat('Y-m', $row->month)->translatedFormat('M/y'),
                'receitas' => (float) $row->receitas,
                'despesas' => (float) $row->despesas,
            ])->toArray();
    }

    /**
     * Retorna a distribuição por forma de pagamento.
     * Respeita ACL e Joins.
     */
    public function getPaymentDistribution(): array
    {
        return $this->getBaseQuery()
            ->selectRaw("
                COALESCE(NULLIF(payment_method, ''), 'sem_metodo') as method,
                COUNT(*) as total
            ")
            ->where('law_financials.status', '!=', 'cancelado')
            ->groupBy('method')
            ->get()
            ->mapWithKeys(fn ($row) => [
                match ($row->method) {
                    'pix'           => 'PIX',
                    'boleto'        => 'Boleto',
                    'cartao'        => 'Cartão',
                    'transferencia' => 'Transferência',
                    'dinheiro'      => 'Dinheiro',
                    default         => 'Outros',
                } => (int) $row->total,
            ])->toArray();
    }

    /**
     * Calcula o total pendente a receber: Receitas com status 'pendente'.
     */
    public function getPendenteReceber(?string $startDate = null, ?string $endDate = null): float
    {
        $query = $this->getBaseQuery()
            ->where('law_financials.tipo', 'receita')
            ->where('law_financials.status', 'pendente');

        if ($startDate) {
            $query->where('law_financials.data_vencimento', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('law_financials.data_vencimento', '<=', $endDate);
        }

        return $query->sum('law_financials.valor') ?? 0;
    }
}
