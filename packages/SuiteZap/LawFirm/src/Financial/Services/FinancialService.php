<?php

namespace SuiteZap\LawFirm\Financial\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use SuiteZap\LawFirm\Financial\Models\Financial;
use SuiteZap\LawFirm\Legal\Models\Processo;

class FinancialService
{
    /**
     * Sincroniza os lançamentos financeiros de um processo.
     *
     * Extraído do ProcessoController::update() — lida com:
     * 1. Deletar registros removidos (IDs ausentes no request)
     * 2. Gerar parcelas automáticas (parcelar=1, parcelas_qtd > 1)
     * 3. Criar/Atualizar registros individuais
     *
     * @param  array  $financeirosData  Array dos dados financeiros do request
     */
    public function syncFinancials(Processo $processo, array $financeirosData): void
    {
        foreach ($financeirosData as $finData) {
            // Pula itens vazios/inválidos (sem nome ou valor definidos)
            if (empty($finData['nome']) && empty($finData['valor'])) {
                continue;
            }

            // CHECK DELETE: Se veio marcado para deletar do front
            if (isset($finData['should_delete']) && $finData['should_delete'] == 1) {
                if (! empty($finData['id'])) {
                    $processo->financeiros()->where('id', $finData['id'])->delete();
                }

                continue;
            }

            // 3a. Novo registro COM parcelamento
            if ($this->shouldGenerateInstallments($finData)) {
                $installments = $this->generateInstallments($finData, $processo->id);

                foreach ($installments as $installment) {
                    $processo->financeiros()->create($installment);
                }

                continue;
            }

            // 3b. Create/Update individual
            $attributes = $this->buildAttributes($finData, $processo->id);

            if (isset($finData['id']) && $finData['id']) {
                $processo->financeiros()->where('id', $finData['id'])->update($attributes);
            } else {
                $processo->financeiros()->create($attributes);
            }
        }
    }

    /**
     * Realiza a baixa rápida (Quick Pay) de um lançamento financeiro.
     */
    public function quickPay(int $id, string $paymentDate, string $paymentMethod): Financial
    {
        $financial = Financial::findOrFail($id);

        $financial->update([
            'status'         => 'pago',
            'payment_date'   => $paymentDate,
            'payment_method' => $paymentMethod,
        ]);

        return $financial;
    }

    /**
     * Cria um lançamento financeiro avulso.
     */
    public function createFinancial(array $data): Financial
    {
        return Financial::create($data);
    }

    /**
     * Atualiza um lançamento financeiro existente.
     */
    public function updateFinancial(int $id, array $data): Financial
    {
        $financial = Financial::findOrFail($id);
        $financial->update($data);

        return $financial;
    }

    /**
     * Remove um lançamento financeiro.
     */
    public function deleteFinancial(int $id): bool
    {
        return Financial::findOrFail($id)->delete();
    }

    /**
     * Prepara os dados para envio de cobrança via WhatsApp.
     *
     * Extrai o telefone do cliente, compila o template de mensagem com base no
     * status de vencimento e retorna um array pronto para o EvolutionService.
     *
     * @param  Financial  $financial  Lançamento carregado com relações processo.person
     * @return array ['phone' => string, 'message' => string]
     *
     * @throws \InvalidArgumentException Se o lançamento já está pago ou se não há pessoa/telefone
     */
    public function prepareBillingWhatsapp(Financial $financial): array
    {
        if ($financial->status === 'pago') {
            throw new \InvalidArgumentException('Lançamento já está pago.');
        }

        $person = $financial->processo->person ?? null;
        if (! $person) {
            throw new \InvalidArgumentException('Nenhuma pessoa associada ao processo.');
        }

        // Extrai o primeiro telefone disponível
        $phone = null;
        $contactNumbers = $person->contact_numbers;
        if (is_array($contactNumbers)) {
            foreach ($contactNumbers as $contact) {
                if (! empty($contact['value'])) {
                    $phone = $contact['value'];
                    break;
                }
            }
        } elseif (is_string($contactNumbers)) {
            $phone = $contactNumbers;
        } elseif ($contactNumbers instanceof \Illuminate\Support\Collection) {
            $phoneObj = $contactNumbers->first();
            $phone = $phoneObj ? $phoneObj->value : null;
        }

        $cleanPhone = preg_replace('/\D/', '', $phone ?? '');
        if (empty($cleanPhone)) {
            throw new \InvalidArgumentException('Cliente não possui telefone cadastrado.');
        }

        // Adiciona prefixo do Brasil se necessário
        if (strlen($cleanPhone) >= 10 && strlen($cleanPhone) <= 11) {
            $cleanPhone = '55'.$cleanPhone;
        }

        // Compila o template de mensagem com base no status de vencimento
        $nomeCliente = explode(' ', trim($person->name))[0];
        $valor = number_format((float) $financial->valor, 2, ',', '.');
        $descricao = $financial->nome;
        $dataVencimento = $financial->data_vencimento ? Carbon::parse($financial->data_vencimento) : null;
        $hoje = Carbon::now()->startOfDay();

        $defaultOverdue = 'Olá {cliente_nome}, verificamos uma pendência de {valor} referente a {descricao}, vencida em {data_vencimento}. Podemos atualizar o boleto?';
        $defaultDue = 'Olá {cliente_nome}, lembrete de vencimento ref {descricao} no valor de {valor} para o dia {data_vencimento}.';

        $templateOverdue = core()->getConfigData('lawfirm.whatsapp_templates.messages.financial_billing_overdue') ?: $defaultOverdue;
        $templateDue = core()->getConfigData('lawfirm.whatsapp_templates.messages.financial_billing_due_today') ?: $defaultDue;

        $dataStr = $dataVencimento ? $dataVencimento->format('d/m/Y') : 'data a confirmar';
        $replaces = [
            '{cliente_nome}'    => $nomeCliente,
            '{valor}'           => 'R$ '.$valor,
            '{descricao}'       => $descricao,
            '{data_vencimento}' => $dataStr,
        ];

        $template = ($dataVencimento && $dataVencimento->lt($hoje)) ? $templateOverdue : $templateDue;
        $message = str_replace(array_keys($replaces), array_values($replaces), $template);

        return [
            'phone'   => $cleanPhone,
            'message' => $message,
        ];
    }

    // =========================================================================
    // MÉTODOS PRIVADOS
    // =========================================================================

    /**
     * Verifica se deve gerar parcelas automáticas.
     *
     * Condições: registro novo (sem ID), parcelar=1, parcelas_qtd > 1.
     */
    private function shouldGenerateInstallments(array $finData): bool
    {
        $isNew = ! isset($finData['id']) || ! $finData['id'];
        $hasInstallments = isset($finData['parcelar'])
            && $finData['parcelar'] == '1'
            && isset($finData['parcelas_qtd'])
            && $finData['parcelas_qtd'] > 1;

        return $isNew && $hasInstallments;
    }

    /**
     * Gera os atributos para cada parcela de um lançamento parcelado.
     *
     * Lógica: divide o valor total igualmente, adiciona o arredondamento
     * na última parcela. Calcula datas com frequência customizável.
     *
     * @param  array  $baseData  Dados do lançamento original
     * @param  int  $processoId  ID do processo pai
     * @return Collection Coleção de arrays de atributos
     */
    public function generateInstallments(array $baseData, int $processoId): Collection
    {
        $qtd = (int) $baseData['parcelas_qtd'];
        $freq = (int) ($baseData['parcelas_frequencia'] ?? 30);
        $totalValue = (float) $baseData['valor'];
        $baseValue = floor(($totalValue / $qtd) * 100) / 100;
        $remainder = round($totalValue - ($baseValue * $qtd), 2);
        $startDate = Carbon::parse($baseData['data_vencimento']);

        $installments = collect();

        for ($i = 1; $i <= $qtd; $i++) {
            $currentValue = $baseValue;
            if ($i == $qtd) {
                $currentValue += $remainder;
            }

            $dueDate = $startDate->copy()->addDays($freq * ($i - 1));

            $installments->push([
                'tipo'            => $baseData['tipo'],
                'nome'            => $baseData['nome']." (Parcela $i/$qtd)",
                'valor'           => $currentValue,
                'data_vencimento' => $dueDate,
                'status'          => $baseData['status'] ?? 'pendente',
                'category'        => $baseData['category'] ?? null,
                'issued_at'       => $baseData['issued_at'] ?? null,
                'payment_method'  => $baseData['payment_method'] ?? null,
                'payment_date'    => ($baseData['status'] ?? 'pendente') === 'pago'
                    ? ($baseData['payment_date'] ?? now()->toDateString())
                    : null,
                'processo_id' => $processoId,
            ]);
        }

        return $installments;
    }

    /**
     * Monta os atributos padrão para um lançamento financeiro.
     */
    private function buildAttributes(array $finData, int $processoId): array
    {
        return [
            'tipo'            => $finData['tipo'],
            'nome'            => $finData['nome'],
            'valor'           => $finData['valor'],
            'data_vencimento' => $finData['data_vencimento'],
            'status'          => $finData['status'] ?? 'pendente',
            'category'        => $finData['category'] ?? null,
            'issued_at'       => $finData['issued_at'] ?? null,
            'payment_method'  => $finData['payment_method'] ?? null,
            'payment_date'    => ($finData['status'] ?? 'pendente') === 'pago'
                ? ($finData['payment_date'] ?? now()->toDateString())
                : null,
            'processo_id' => $processoId,
        ];
    }
}
