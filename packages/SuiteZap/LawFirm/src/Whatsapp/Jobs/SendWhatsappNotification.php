<?php

namespace SuiteZap\LawFirm\Whatsapp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SaasFileService;
use SuiteZap\LawFirm\Whatsapp\Services\EvolutionService;

class SendWhatsappNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phoneNumber;

    protected $message;

    protected $attachments;

    protected $tenantId;

    /**
     * Create a new job instance.
     *
     * @param  array  $attachments  Arrays with 'path' and 'name'
     */
    public function __construct(string $phoneNumber, string $message, array $attachments = [], ?string $tenantId = null)
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
        $this->attachments = $attachments;
        // Injeta tenantId se passado, senão pega do request atual
        $this->tenantId = $tenantId ?? MotherShipService::getTenantId();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Força a restauração do Tenant ID no Job
        if ($this->tenantId) {
            config(['lawfirm.tenant_id' => $this->tenantId]);
        }

        // Verifica a configuração diretamente do MotherShip (Zero .env)
        $config = MotherShipService::getEvolutionConfig();

        if (! $config || empty($config['instance']) || empty($config['token'])) {
            Log::error("WHATSAPP ERROR: Falha de configuração Evolution API para o Tenant {$this->tenantId}. Cancelando disparo.");

            return; // Degradação graciosa
        }

        $evolutionService = new EvolutionService;

        // Envia mensagem de texto
        if (! empty($this->message)) {
            $response = $evolutionService->sendMessage(
                $config['instance'],
                $this->phoneNumber,
                $this->message
            );

            if (! $response || ! isset($response['success']) || ! $response['success']) {
                Log::error("WHATSAPP ERROR: Falha ao enviar mensagem para {$this->phoneNumber}. Erro: ".json_encode($response));
            }
        }

        // Processa anexos usando SaasFileService (Proibido usar Storage::)
        if (! empty($this->attachments)) {
            foreach ($this->attachments as $attachment) {
                try {
                    // Obtém a URL assinada direta do S3/MinIO
                    $signedUrl = SaasFileService::getSignedUrl($attachment['path']);

                    // TODO: Implementar o disparo de mídia na EvolutionService
                    // $evolutionService->sendMedia($config['instance'], $this->phoneNumber, $signedUrl, $attachment['name'] ?? '');

                } catch (\Exception $e) {
                    Log::error("WHATSAPP ERROR: Falha ao processar anexo {$attachment['path']} usando SaasFileService. Erro: ".$e->getMessage());
                }
            }
        }
    }
}
