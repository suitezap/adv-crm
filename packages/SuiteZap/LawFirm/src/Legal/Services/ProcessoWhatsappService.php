<?php

namespace SuiteZap\LawFirm\Legal\Services;

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
    private function resolvePhone(\SuiteZap\LawFirm\Legal\Models\Processo $processo): ?string
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
     * Build the portal link for a given Processo.
     */
    private function buildPortalLink(\SuiteZap\LawFirm\Legal\Models\Processo $processo): string
    {
        return route('lawfirm.public.portal.index', [
            'id'    => $processo->id,
            'token' => hash_hmac('sha256', $processo->id, config('app.key')),
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

        $template = core()->getConfigData('lawfirm.whatsapp_templates.messages.registration_request')
            ?: "Olá {cliente_nome}. Referente ao processo {processo_titulo}, precisamos que atualize suas informações cadastrais.\nUtilize o link: {link_portal}";

        $msg = str_replace(
            ['{cliente_nome}', '{processo_titulo}', '{link_portal}'],
            [$processo->person->name, $processo->titulo ?? 'Processo', $this->buildPortalLink($processo)],
            $template
        );

        $config = $this->getEvolutionConfig();
        if (! $config) {
            return ['sent' => false, 'warning' => 'Mensagem não enviada. WhatsApp não está configurado para o escritório.', 'error' => null];
        }

        $this->evolutionService->sendMessage($config['instance'], $phone, $msg);

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

        $template = core()->getConfigData('lawfirm.whatsapp_templates.messages.documents_request')
            ?: "Olá {cliente_nome}. Referente ao processo {processo_titulo}, por favor, nos envie os seguintes documentos pendentes:\n\n{documentos_pendentes}\n\nVocê pode enviá-los por aqui mesmo ou através do nosso portal: {link_portal}";

        $msg = str_replace(
            ['{cliente_nome}', '{processo_titulo}', '{documentos_pendentes}', '{link_portal}'],
            [$processo->person->name, $processo->titulo ?? 'Processo', $docsList, $this->buildPortalLink($processo)],
            $template
        );

        $config = $this->getEvolutionConfig();
        if (! $config) {
            return ['sent' => false, 'warning' => 'Mensagem não enviada. WhatsApp não está configurado para o escritório.', 'error' => null];
        }

        $this->evolutionService->sendMessage($config['instance'], $phone, $msg);

        return ['sent' => true, 'warning' => null, 'error' => null];
    }
}
