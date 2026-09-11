<?php

namespace SuiteZap\LawFirm\Whatsapp\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Legal\Models\ProcessoWhatsappMessage;
use SuiteZap\LawFirm\Legal\Models\WhatsappImport;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\Whatsapp\Services\EvolutionService;
use Webkul\User\Models\User;

class ImportProcessoWhatsappMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $processoId;

    protected $remoteJid;

    protected $startDate;

    protected $endDate;

    protected $userId;

    protected $tenantId;

    public function __construct($processoId, $remoteJid, $startDate, $endDate, $userId, $tenantId)
    {
        $this->processoId = $processoId;
        $this->remoteJid = $remoteJid;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
    }

    public function handle(EvolutionService $evolutionService)
    {
        // Restore context if needed for SaaS environments
        if (function_exists('core') && method_exists(core(), 'setCurrentTenantId')) {
            core()->setCurrentTenantId($this->tenantId);
        }

        Log::info("Starting WhatsApp Import for Processo {$this->processoId}, JID {$this->remoteJid}");

        // Create the import session record
        $import = WhatsappImport::create([
            'processo_id'  => $this->processoId,
            'remote_jid'   => $this->remoteJid,
            'start_date'   => $this->startDate,
            'end_date'     => $this->endDate,
            'status'       => 'processing',
            'imported_by'  => $this->userId,
        ]);

        try {
            $config = MotherShipService::getEvolutionConfig();

            if (! $config || empty($config['instance'])) {
                Log::error("Evolution API not configured for Tenant {$this->tenantId}. Cannot run import.");
                $import->update(['status' => 'failed']);

                return;
            }

            $instanceName = $config['instance'];

            $response = $evolutionService->fetchMessagesByDateRange(
                $instanceName,
                $this->remoteJid,
                $this->startDate,
                $this->endDate,
                1000
            );

            if (! $response['success'] || empty($response['data']['messages'])) {
                Log::warning("No messages imported or failed. Processo: {$this->processoId}");
                $import->update(['status' => 'failed', 'message_count' => 0]);
                $this->notifyUser("Falha ou nenhuma mensagem encontrada no período solicitado para o Processo #{$this->processoId}.");

                return;
            }

            $messages = $response['data']['messages'];
            $importedCount = 0;
            $contactName = null;

            foreach ($messages as $msg) {
                $msgId = $msg['key']['id'] ?? null;
                if (! $msgId) {
                    continue;
                }

                // Extract text from the Baileys message structure
                $messageContent = $msg['message'] ?? [];
                $text = '';

                if (isset($messageContent['conversation'])) {
                    $text = $messageContent['conversation'];
                } elseif (isset($messageContent['extendedTextMessage']['text'])) {
                    $text = $messageContent['extendedTextMessage']['text'];
                } elseif (isset($messageContent['imageMessage'])) {
                    $caption = $messageContent['imageMessage']['caption'] ?? '';
                    $text = empty($caption) ? '📷 [Imagem]' : "📷 [Imagem] {$caption}";
                } elseif (isset($messageContent['videoMessage'])) {
                    $caption = $messageContent['videoMessage']['caption'] ?? '';
                    $text = empty($caption) ? '🎥 [Vídeo]' : "🎥 [Vídeo] {$caption}";
                } elseif (isset($messageContent['audioMessage'])) {
                    $text = '🎵 [Áudio]';
                } elseif (isset($messageContent['documentMessage'])) {
                    $fileName = $messageContent['documentMessage']['fileName'] ?? 'Arquivo Anexado';
                    $text = "📄 [Documento] {$fileName}";
                } elseif (isset($messageContent['stickerMessage'])) {
                    $text = '🧩 [Figurinha]';
                } elseif (isset($messageContent['contactMessage'])) {
                    $displayName = $messageContent['contactMessage']['displayName'] ?? 'Contato';
                    $text = "👤 [Contato] {$displayName}";
                } elseif (isset($messageContent['locationMessage'])) {
                    $text = '📍 [Localização]';
                } else {
                    $fallbackRawText = current($messageContent)['text'] ?? current($messageContent)['caption'] ?? '';
                    $text = ! empty($fallbackRawText) ? $fallbackRawText : '[Mensagem Multimídia ou Sistema]';
                    if (is_array($text)) {
                        $text = json_encode($text);
                    }
                }

                $sender = $msg['pushName'] ?? null;
                $isFromMe = $msg['key']['fromMe'] ?? false;

                // Capture the first contact name from a non-self message
                if (! $isFromMe && $sender && ! $contactName) {
                    $contactName = $sender;
                }

                $timestamp = $msg['messageTimestamp'] ?? time();
                if (is_array($timestamp) && isset($timestamp['low'])) {
                    $timestamp = $timestamp['low'];
                }
                $dateTime = Carbon::createFromTimestamp($timestamp);

                ProcessoWhatsappMessage::updateOrCreate(
                    ['message_id' => $msgId],
                    [
                        'processo_id'       => $this->processoId,
                        'import_id'         => $import->id,
                        'remote_jid'        => $this->remoteJid,
                        'sender_name'       => $sender,
                        'message_text'      => $text,
                        'message_timestamp' => $dateTime,
                        'is_from_me'        => $isFromMe,
                        'payload'           => $msg,
                    ]
                );
                $importedCount++;
            }

            // Finalize the import record
            $import->update([
                'status'        => 'completed',
                'message_count' => $importedCount,
                'contact_name'  => $contactName,
            ]);

            Log::info("WhatsApp Import complete. {$importedCount} messages saved. Import ID: {$import->id}");

            $this->notifyUser("✅ Importação de WhatsApp concluída no Processo #{$this->processoId}.\n\nForam recuperadas {$importedCount} mensagens do histórico.");

        } catch (\Throwable $e) {
            Log::error("WhatsApp Import failed for Processo {$this->processoId}: ".$e->getMessage());
            $import->update(['status' => 'failed']);
            $this->notifyUser("❌ Erro na importação do WhatsApp para o Processo #{$this->processoId}: ".$e->getMessage());
        }
    }

    protected function notifyUser($message)
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        // Notify the user directly on their own WhatsApp using Evolution API
        // if they have configured a contact_number on their profile.
        $config = MotherShipService::getEvolutionConfig();
        if ($config && ! empty($config['instance']) && $user->contact_number) {
            $evoService = app(EvolutionService::class);

            $phone = preg_replace('/\D/', '', $user->contact_number);
            if (strlen($phone) >= 10) {
                if (strpos($phone, '55') !== 0) {
                    $phone = '55'.$phone;
                }
                $remoteJid = "{$phone}@s.whatsapp.net";

                // Fire and forget
                try {
                    $evoService->sendMessage($config['instance'], $remoteJid, "🤖 *Robô CRM*\n\n".$message);
                } catch (\Exception $e) {
                    Log::error('Não foi possível enviar notificação ao usuário sobre importação WH: '.$e->getMessage());
                }
            }
        }
    }
}
