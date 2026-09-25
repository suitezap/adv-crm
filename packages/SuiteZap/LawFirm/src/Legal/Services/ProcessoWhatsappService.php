<?php

namespace SuiteZap\LawFirm\Legal\Services;

use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Atendimento\Services\ChatwootService;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\Legal\Repositories\ProcessoRepository;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\Whatsapp\Services\EvolutionService;

/**
 * ProcessoWhatsappService
 *
 * Handles all WhatsApp message dispatch related to Processos.
 * Extracted from ProcessoController as per Skinny Controller / DDD best practices.
 * Single Responsibility: WhatsApp orchestration for Processo domain.
 *
 * @since v3.52.1
 */
class ProcessoWhatsappService
{
    public function __construct(
        protected ProcessoRepository $processoRepository,
        protected EvolutionService $evolutionService,
    ) {}

    /**
     * Extract the first phone number from a Processo's associated Person.
     */
    private function resolvePhone(Processo $processo): ?string
    {
        if (! $processo->person) {
            return null;
        }

        $contactNumbers = collect($processo->person->contact_numbers);
        $phoneData = $contactNumbers->first();

        return is_object($phoneData)
            ? $phoneData->value
            : ($phoneData['value'] ?? null);
    }

    /**
     * Build the portal link for a given Processo (token com expiração de 30 dias).
     */
    private function buildPortalLink(Processo $processo): string
    {
        $exp = time() + (30 * 86400);

        return route('lawfirm.public.portal.index', [
            'id'    => $processo->id,
            'token' => 'exp.'.$exp.'.'.hash_hmac('sha256', $processo->id.'|'.$exp, config('app.key')),
        ]);
    }

    /**
     * Retrieve Evolution API config and return null if not configured.
     */
    private function getEvolutionConfig(): ?array
    {
        $config = MotherShipService::getEvolutionConfig();

        return (! empty($config['instance'])) ? $config : null;
    }

    /**
     * Send a WhatsApp message requesting the client to update their registration.
     *
     * @return array{sent: bool, warning: ?string, error: ?string}
     */
    public function sendRegistrationRequest(int $processoId): array
    {
        $processo = $this->processoRepository->with('person')->findOrFail($processoId);

        $phone = $this->resolvePhone($processo);
        if (! $phone) {
            return ['sent' => false, 'warning' => null, 'error' => 'O cliente não possui telefone cadastrado.'];
        }

        $portalLink = $this->buildPortalLink($processo);

        $template = core()->getConfigData('lawfirm.whatsapp_templates.messages.registration_request')
            ?: "Olá {cliente_nome}. Referente ao processo {processo_titulo}, precisamos que atualize suas informações cadastrais.\nUtilize o link: {link_portal}";

        $fallbackMsg = str_replace(
            ['{cliente_nome}', '{processo_titulo}', '{link_portal}'],
            [$processo->person->name, $processo->titulo ?? 'Processo', $portalLink],
            $template
        );

        // Se houver botão CTA, podemos limpar a menção crua ao link no corpo da mensagem do botão
        $description = str_replace(
            ['{cliente_nome}', '{processo_titulo}', '{link_portal}'],
            [$processo->person->name, $processo->titulo ?? 'Processo', ''],
            $template
        );
        $description = trim(preg_replace('/(Utilize o link:?|Acesse o link:?|pelo link:?)\s*$/im', '', $description));

        $config = $this->getEvolutionConfig();
        if (! $config) {
            return ['sent' => false, 'warning' => 'Mensagem não enviada. WhatsApp não está configurado para o escritório.', 'error' => null];
        }

        $buttons = [
            [
                'type'        => 'url',
                'displayText' => '📝 Atualizar Cadastro',
                'url'         => $portalLink,
            ],
        ];

        $title = $processo->titulo ? "Processo: {$processo->titulo}" : 'Atualização Cadastral';
        $footer = config('app.name', 'Portal do Cliente');

        $this->evolutionService->sendButtons(
            $config['instance'],
            $phone,
            $title,
            $description,
            $buttons,
            $footer,
            $fallbackMsg
        );

        return ['sent' => true, 'warning' => null, 'error' => null];
    }

    /**
     * Send a WhatsApp message listing pending documents to the client.
     *
     * @return array{sent: bool, warning: ?string, error: ?string}
     */
    public function sendDocumentsRequest(int $processoId): array
    {
        $processo = $this->processoRepository->with(['person', 'documents'])->findOrFail($processoId);

        $phone = $this->resolvePhone($processo);
        if (! $phone) {
            return ['sent' => false, 'warning' => null, 'error' => 'O cliente não possui telefone cadastrado.'];
        }

        $pendingDocs = $processo->documents->where('status', 'pending');
        if ($pendingDocs->isEmpty()) {
            return ['sent' => false, 'warning' => 'Não há documentos pendentes para solicitar.', 'error' => null];
        }

        $docsList = $pendingDocs->map(fn ($doc) => "- {$doc->name}")->implode("\n");
        $portalLink = $this->buildPortalLink($processo);

        $template = core()->getConfigData('lawfirm.whatsapp_templates.messages.documents_request')
            ?: "Olá {cliente_nome}. Referente ao processo {processo_titulo}, por favor, nos envie os seguintes documentos pendentes:\n\n{documentos_pendentes}\n\nVocê pode enviá-los por aqui mesmo ou através do nosso portal: {link_portal}";

        $fallbackMsg = str_replace(
            ['{cliente_nome}', '{processo_titulo}', '{documentos_pendentes}', '{link_portal}'],
            [$processo->person->name, $processo->titulo ?? 'Processo', $docsList, $portalLink],
            $template
        );

        $description = str_replace(
            ['{cliente_nome}', '{processo_titulo}', '{documentos_pendentes}', '{link_portal}'],
            [$processo->person->name, $processo->titulo ?? 'Processo', $docsList, ''],
            $template
        );
        $description = trim(preg_replace('/(ou através do nosso portal:?|pelo link:?|Utilize o link:?)\s*$/im', '', $description));

        $config = $this->getEvolutionConfig();
        if (! $config) {
            return ['sent' => false, 'warning' => 'Mensagem não enviada. WhatsApp não está configurado para o escritório.', 'error' => null];
        }

        $buttons = [
            [
                'type'        => 'url',
                'displayText' => '📄 Enviar Documentos',
                'url'         => $portalLink,
            ],
        ];

        $title = $processo->titulo ? "Processo: {$processo->titulo}" : 'Solicitação de Documentos';
        $footer = config('app.name', 'Portal do Cliente');

        $this->evolutionService->sendButtons(
            $config['instance'],
            $phone,
            $title,
            $description,
            $buttons,
            $footer,
            $fallbackMsg
        );

        return ['sent' => true, 'warning' => null, 'error' => null];
    }

    /**
     * Send a security notification regarding the secret key.
     * Always uses the 'primary' (default) Evolution config for sending.
     *
     * @return array{sent: bool, warning: ?string, error: ?string}
     */
    public function sendSecurityNotification(int $processoId): array
    {
        $processo = $this->processoRepository->with('person')->findOrFail($processoId);

        $phone = $this->resolvePhone($processo);
        if (! $phone) {
            return ['sent' => false, 'warning' => null, 'error' => 'O cliente não possui telefone cadastrado.'];
        }

        if (empty($processo->sercreta)) {
            return ['sent' => false, 'warning' => null, 'error' => 'Chave secreta não foi gerada para este processo.'];
        }

        // Must use primary instance (advdf2g)
        $config = MotherShipService::getEvolutionConfig('default');
        if (! $config || empty($config['instance'])) {
            return ['sent' => false, 'warning' => 'Mensagem não enviada. Instância de Notificações não está configurada.', 'error' => null];
        }

        $title = 'Resposta Rápida';
        $description = '🔒 *Aviso de segurança:* Por segurança, este é o canal oficial de comunicação sobre seu processo. Para que suas mensagens sejam encaminhadas corretamente, *informe sempre o código de segurança deste processo: '.$processo->sercreta."*. Dúvidas e informações devem ser tratadas preferencialmente por este WhatsApp.\n\nEscolha uma das opções abaixo:";
        $fallbackMsg = '🔒 *Aviso de segurança:* Por segurança, este é o canal oficial de comunicação sobre seu processo. Para que suas mensagens sejam encaminhadas corretamente, *informe sempre o código de segurança deste processo: '.$processo->sercreta."*. Dúvidas e informações devem ser tratadas preferencialmente por este WhatsApp.\n\nResponda com \"✅ Estou Ciente\" para confirmar a leitura.";

        $buttons = [
            [
                'type'        => 'reply',
                'displayText' => '✅ Estou Ciente',
                'id'          => 'opt_confirm_sec_'.$processo->id,
            ],
        ];

        $footer = 'Evolution API';

        $response = $this->evolutionService->sendButtons(
            $config['instance'],
            $phone,
            $title,
            $description,
            $buttons,
            $footer,
            $fallbackMsg
        );

        if (! ($response['success'] ?? false)) {
            return ['sent' => false, 'warning' => null, 'error' => 'Falha ao enviar aviso de segurança: '.($response['error'] ?? 'Erro desconhecido')];
        }

        // Update status to awaiting
        $processo->update([
            'security_notif_status' => 'awaiting',
            'status' => 'Aguardando Cliente'
        ]);

        // Update Chatwoot conversation status if needed
        // For Chatwoot, we apply the status label `awaiting_client` or similar
        // We can leverage ChatwootService directly
        try {
            $chatwoot = new ChatwootService;
            // Chatwoot service manages status labels globally
            $chatwoot->syncContactLabels($processo->person_id, ['awaiting_client'], []);
        } catch (\Exception $e) {
            Log::error('Failed to update Chatwoot label for security notif: '.$e->getMessage());
        }

        return ['sent' => true, 'warning' => null, 'error' => null];
    }
}
