<?php

namespace SuiteZap\LawFirm\Atendimento\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Atendimento\Services\ChatwootService;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

/**
 * ProcessChatwootMessageCreatedJob
 *
 * Processa o evento 'message_created' recebido via webhook do Chatwoot.
 *
 * Dual Inbox Routing (ARCHITECTURE_LawFirm_orient.md §14.4):
 *   - inbox_id (humano)           → Mensagem de atendimento humano
 *   - assistant_inbox_id (IA)     → Mensagem do assistente IA — futura integração N8N/AI
 *
 * Filtros obrigatórios:
 *   - Ignora mensagens do tipo 'outgoing' (enviadas pelo bot — evita loop).
 *   - Ignora mensagens 'activity' (eventos de sistema do Chatwoot).
 *   - Processa apenas mensagens 'incoming' de clientes reais.
 *
 * Regra 4 SKILL.md: Jobs NUNCA propagam throw.
 *                   Falha graciosamente com Log::error() + return.
 *
 * @since v3.54.2
 */
class ProcessChatwootMessageCreatedJob implements ShouldQueue
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
     * @param  array  $payload  O payload completo do webhook 'message_created'
     */
    public function __construct(protected array $payload) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $messageType    = $this->payload['message_type'] ?? null;
        $conversationId = $this->payload['conversation']['id'] ?? null;
        $inboxId        = $this->payload['inbox_id'] ?? null;
        $senderName     = $this->payload['sender']['name'] ?? 'Desconhecido';
        $content        = substr($this->payload['content'] ?? '', 0, 80);

        // ── Filtros de segurança — evita loops e mensagens de sistema ──
        if (in_array($messageType, ['outgoing', 'activity'], true)) {
            // Silently ignore — não logar para evitar spam no log
            return;
        }

        if (! $conversationId) {
            Log::error('[ProcessChatwootMessageCreated] Payload sem conversation_id — abortando.');

            return;
        }

        Log::info('[ProcessChatwootMessageCreated] Mensagem recebida.', [
            'conversation_id' => $conversationId,
            'inbox_id'        => $inboxId,
            'sender'          => $senderName,
            'preview'         => $content,
        ]);

        // ── Dual Inbox Routing ──
        $config = MotherShipService::getChatwootConfig();

        if (! $config) {
            Log::warning('[ProcessChatwootMessageCreated] Chatwoot não configurado para este tenant.');

            return;
        }

        $humanInboxId     = (int) ($config['inbox_id'] ?? 0);
        $assistantInboxId = (int) ($config['assistant_inbox_id'] ?? 0);
        $currentInboxId   = (int) $inboxId;

        if ($assistantInboxId > 0 && $currentInboxId === $assistantInboxId) {
            // Mensagem chegou no inbox da IA — futuro: disparar AI Job
            Log::info('[ProcessChatwootMessageCreated] Roteado para Inbox IA.', [
                'conversation_id'   => $conversationId,
                'assistant_inbox_id' => $assistantInboxId,
            ]);

            // TODO v3.55: dispatch(new ProcessChatwootAiReplyJob($this->payload));

            return;
        }

        // Mensagem no inbox de atendimento humano (ou inbox genérico)
        // TODO v3.55: registrar mensagem no histórico do Lead vinculado
        // $phone = $this->payload['sender']['phone_number'] ?? null;
        // if ($phone) {
        //     LinkChatwootMessageToLeadJob::dispatch($conversationId, $this->payload);
        // }
    }
}
