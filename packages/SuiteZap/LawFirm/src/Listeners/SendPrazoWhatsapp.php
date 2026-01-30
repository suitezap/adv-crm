<?php

namespace SuiteZap\LawFirm\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Events\PrazoCreated;
use SuiteZap\LawFirm\Services\Whatsapp\EvolutionService;

class SendPrazoWhatsapp
{
    use InteractsWithQueue;

    /**
     * The Evolution service instance.
     *
     * @var \SuiteZap\LawFirm\Services\Whatsapp\EvolutionService
     */
    protected $evolutionService;

    /**
     * Create the event listener.
     *
     * @param  \SuiteZap\LawFirm\Services\Whatsapp\EvolutionService  $evolutionService
     * @return void
     */
    public function __construct(EvolutionService $evolutionService)
    {
        $this->evolutionService = $evolutionService;
    }

    /**
     * Handle the event.
     *
     * @param  \SuiteZap\LawFirm\Events\PrazoCreated  $event
     * @return void
     */
    public function handle(PrazoCreated $event)
    {
        Log::info("--- START WHATSAPP LISTENER: Novo Prazo ID {$event->prazo->id} ---");

        try {
            // 1. Carregar Config
            $template = core()->getConfigData('lawfirm.whatsapp_templates.messages.new_prazo_client');
            if (empty($template)) {
                Log::warning("Abortando: Template de mensagem não configurado.");
                return;
            }

            // 2. Carregar Relacionamentos (Evitar erro de lazy loading)
            $prazo = $event->prazo;

            // Garantir carregamento da cadeia: processo -> person
            // O model Prazo não tem 'person' direto, mas 'processo' tem 'person'.
            // Verificamos se processo já está carregado, se não carregamos.
            $prazo->loadMissing(['processo.person']);

            $processo = $prazo->processo;
            if (!$processo) {
                Log::warning("Abortando: Prazo sem Processo vinculado.");
                return;
            }

            $person = $processo->person;
            if (!$person) {
                Log::warning("Abortando: Processo do Prazo sem Pessoa vinculada.");
                return;
            }

            // 3. Obter Telefone
            // Krayin geralmente armazena em 'contact_numbers' (JSON array ou Relation se customizado, 
            // mas no Person.php padrão é cast 'array').
            $phone = null;
            $contactNumbers = $person->contact_numbers;

            // Log raw contact numbers for debug
            Log::info("Raw Contact Numbers: " . json_encode($contactNumbers));

            if (is_array($contactNumbers)) {
                foreach ($contactNumbers as $contact) {
                    if (isset($contact['value'])) {
                        $phone = $contact['value'];
                        break;
                    }
                }
            } elseif ($contactNumbers instanceof \Illuminate\Support\Collection) {
                $phoneObj = $contactNumbers->first();
                $phone = $phoneObj ? $phoneObj->value : null;
            }

            Log::info("Pessoa: {$person->name} | Telefone Encontrado: " . ($phone ?? 'NENHUM'));

            if (empty($phone)) {
                Log::warning("Abortando: Nenhum telefone encontrado para a pessoa.");
                return;
            }

            // 4. Preparar Mensagem
            $msg = str_replace(
                ['{cliente_nome}', '{prazo_titulo}', '{prazo_data}', '{prazo_descricao}'],
                [
                    $person->name,
                    $prazo->titulo ?? 'Prazo',
                    $prazo->data_vencimento ? $prazo->data_vencimento->format('d/m/Y') : date('d/m/Y'),
                    $prazo->descricao ?? ''
                ],
                $template
            );

            Log::info("Enviando Mensagem: {$msg}");

            // 5. Enviar via Service
            // Enviar via EvolutionService usando sendMessage(instanceName, number, text)
            // Send Message
            $instanceName = env('EVOLUTION_INSTANCE_NAME');
            Log::info("LawFirm: Usando Instância Evolution: '{$instanceName}'");

            // Cleaning phone
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($cleanPhone) <= 11) {
                $cleanPhone = '55' . $cleanPhone;
            }

            $result = $this->evolutionService->sendMessage($instanceName, $cleanPhone, $msg);

            Log::info("Resultado API: " . json_encode($result));

        } catch (\Exception $e) {
            Log::error("ERRO FATAL NO LISTENER WHATSAPP: " . $e->getMessage());
            Log::error($e->getTraceAsString());
        }

        Log::info("--- END WHATSAPP LISTENER ---");
    }
}
