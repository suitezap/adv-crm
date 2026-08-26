<?php

namespace SuiteZap\LawFirm\Atendimento\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Atendimento\Services\ChatwootService;

/**
 * ProcessChatwootConversationCreatedJob
 *
 * Processa o evento 'conversation_created' recebido via webhook do Chatwoot.
 *
 * Responsabilidades atuais:
 *   - Loga a criação da conversa para rastreabilidade.
 *   - Atribui label inicial 'LD_NOVO' via ChatwootService (se possível).
 *
 * Responsabilidades futuras (TODO):
 *   - Vincular conversa ao Lead existente via phone number.
 *   - Criar Lead automaticamente se não encontrado.
 *   - Disparar triagem inicial via Whatsapp_Triagem (módulo opcional).
 *
 * Regra 4 SKILL.md: Jobs NUNCA propagam throw.
 *                   Falha graciosamente com Log::error() + return.
 *
 * @since v3.54.2
 */
class ProcessChatwootConversationCreatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de tentativas antes de falhar definitivamente.
     */
    public int $tries = 3;

    /**
     * Timeout em segundos para este Job.
     */
    public int $timeout = 30;

    /**
     * Create a new job instance.
     *
     * @param  array  $payload  O payload completo do webhook 'conversation_created'
     */
    public function __construct(protected array $payload) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $conversationId = $this->payload['id'] ?? null;
        $contactName = $this->payload['meta']['sender']['name'] ?? 'Desconhecido';
        $inboxId = $this->payload['inbox_id'] ?? null;

        Log::info('[ProcessChatwootConversationCreated] Nova conversa recebida.', [
            'conversation_id' => $conversationId,
            'contact'         => $contactName,
            'inbox_id'        => $inboxId,
        ]);

        if (! $conversationId) {
            Log::error('[ProcessChatwootConversationCreated] Payload sem conversation_id — abortando.');

            return;
        }

        // ── Tentar atribuir label inicial 'LD_NOVO' via ChatwootService ──
        try {
            $service = new ChatwootService;

            $stagePool = ['LD_NOVO', 'LD_ACOMP', 'LD_QUAL', 'LD_NEG', 'LD_GANHO', 'LD_PERD'];

            $service->addLabels((int) $conversationId, ['LD_NOVO']);

            Log::info('[ProcessChatwootConversationCreated] Label LD_NOVO atribuída.', [
                'conversation_id' => $conversationId,
            ]);
        } catch (\RuntimeException $e) {
            // ChatwootService lança RuntimeException quando não há config para o tenant
            // Regra 4: não propaga, apenas loga
            Log::warning('[ProcessChatwootConversationCreated] Chatwoot não configurado — label não atribuída.', [
                'conversation_id' => $conversationId,
                'reason'          => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[ProcessChatwootConversationCreated] Erro inesperado ao atribuir label.', [
                'conversation_id' => $conversationId,
                'error'           => $e->getMessage(),
            ]);
        }

        // TODO v3.55: vincular conversa ao Lead via contact phone number
        // $phone = $this->payload['meta']['sender']['phone_number'] ?? null;
        // if ($phone) {
        //     dispatch(new LinkChatwootConversationToLeadJob($conversationId, $phone));
        // }
    }
}
