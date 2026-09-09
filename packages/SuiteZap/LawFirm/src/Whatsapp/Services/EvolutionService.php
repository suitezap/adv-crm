<?php

namespace SuiteZap\LawFirm\Whatsapp\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

class EvolutionService
{
    protected $client;

    protected $baseUrl;

    protected $instanceName;

    protected $apiKey;

    public function __construct()
    {
        $this->setConfigType('default');
    }

    /**
     * Permite reconfigurar o serviço para outra instância (ex: atendimento)
     */
    public function setConfigType(string $type = 'default')
    {
        // Tenta carregar do Banco de Dados (MotherShip)
        $config = MotherShipService::getEvolutionConfig($type);

        if ($config) {
            $this->baseUrl = $config['base_url'];
            $this->apiKey = $config['token'];
            $this->instanceName = $config['instance'];
        } else {
            // 2. Fallback para banco local (Configurações do CRM Admin - tenant isolado)
            try {
                $this->baseUrl = core()->getConfigData('lawfirm.settings.general.evolution_api_url');
                $this->apiKey = core()->getConfigData('lawfirm.settings.general.evolution_api_key');
            } catch (\Exception $e) {
                // Falha silenciosa no bootstrap caso a tabela core_config ainda não exista (testes)
                $this->baseUrl = null;
                $this->apiKey = null;
            }
            $this->instanceName = null;
        }

        // Normalize URL
        if ($this->baseUrl) {
            $this->baseUrl = rtrim($this->baseUrl, '/');
        }

        if ($this->baseUrl && $this->apiKey) {
            $this->client = new Client([
                'base_uri' => $this->baseUrl,
                'headers'  => [
                    'apikey'       => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 30,
            ]);
        }

        return $this;
    }

    /**
     * Helper to handle Guzzle requests nicely
     */
    protected function request($method, $uri, $data = [])
    {
        if (! $this->client) {
            return [
                'success' => false,
                'error'   => 'Evolution API não configurada (URL ou Key ausentes).',
            ];
        }

        try {
            $options = [];
            if (! empty($data)) {
                $options['json'] = $data;
            }

            $response = $this->client->request($method, $uri, $options);
            $body = json_decode($response->getBody(), true);

            return ['success' => true, 'data' => $body];

        } catch (ClientException $e) {
            // 4xx errors
            $response = $e->getResponse();
            $body = json_decode($response->getBody(), true);

            return [
                'success' => false,
                'error'   => $body['message'] ?? $body['error'] ?? $e->getMessage(),
            ];

        } catch (\Exception $e) {
            // Other errors
            Log::error('Evolution API Error: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Cria a instância (se não existir)
     */
    public function createInstance($instanceName)
    {
        return $this->request('POST', '/instance/create', [
            'instanceName' => $instanceName,
            'qrcode'       => true,
            'integration'  => 'WHATSAPP-BAILEYS',
        ]);
    }

    /**
     * Retorna o status/QR code da conexão
     */
    public function connectInstance($instanceName)
    {
        // Primeiro tenta conexão
        return $this->request('GET', "/instance/connect/{$instanceName}");
    }

    /**
     * Busca informações da instância (estado, perfil, etc)
     */
    public function fetchInstance($instanceName)
    {
        return $this->request('GET', "/instance/fetchInstances?instanceName={$instanceName}");
    }

    /**
     * Deslogar a conta em vez de apagar a instância e perder webhooks
     */
    public function logoutInstance($instanceName)
    {
        return $this->request('DELETE', "/instance/logout/{$instanceName}");
    }

    /**
     * Desconecta (Logout) e Deleta instância
     */
    public function disconnectInstance($instanceName)
    {
        if (! $this->client) {
            return null;
        }

        try {
            // Tenta fazer Logout primeiro
            $this->client->delete("/instance/logout/{$instanceName}");
        } catch (\Exception $e) {
            // Ignora erro se já estiver deslogado
        }

        // Força a deleção da instância na API para garantir limpeza
        try {
            return $this->client->delete("/instance/delete/{$instanceName}");
        } catch (\Exception $e) {
            // Retorna null ou lança exceção controlada
            Log::error('Erro ao deletar instância Evolution: '.$e->getMessage());
            throw $e; // Re-throw to let controller handle it
        }
    }

    /**
     * Envia mensagem de texto
     */
    public function sendMessage($instanceName, $number, $text)
    {
        return $this->request('POST', "/message/sendText/{$instanceName}", [
            'number'      => $number,
            'text'        => $text,
            'delay'       => 1200,
            'linkPreview' => false,
        ]);
    }

    /**
     * Busca histórico de mensagens de um contato e filtra as relativas a um intervalo de datas (localmente).
     *
     * A Evolution API não suporta filtrar por key.fromMe no where clause.
     * Estratégia: uma única query usando OR para remoteJid ou remoteJidAlt
     * (já que as mensagens recebidas muitas vezes armazenam o @lid no remoteJid e
     * o número real no remoteJidAlt).
     * + post-filter rígido em PHP para evitar vazamento.
     */
    public function fetchMessagesByDateRange($instanceName, $remoteJid, $startDate = null, $endDate = null, $limit = 500)
    {
        $response = $this->request('POST', "/chat/findMessages/{$instanceName}", [
            'where' => [
                'OR' => [
                    ['key' => ['remoteJid' => $remoteJid]],
                    ['key' => ['remoteJidAlt' => $remoteJid]]
                ]
            ],
            'limit' => (int) $limit,
        ]);

        if (! $response['success'] || empty($response['data']['messages'])) {
            return $response;
        }

        $raw = $response['data']['messages']['records'] ?? $response['data']['messages'] ?? [];

        // Strict post-filter: only keep messages whose key.remoteJid or key.remoteJidAlt exactly matches the target.
        // This prevents bleed from group chats or broadcast lists where the same JID appears.
        $messages = [];
        $seen = [];
        foreach ($raw as $msg) {
            $msgKeyId     = $msg['key']['id'] ?? null;
            $msgRemoteJid = $msg['key']['remoteJid'] ?? '';
            $msgRemoteJidAlt = $msg['key']['remoteJidAlt'] ?? '';

            $matchesJid = ($msgRemoteJid === $remoteJid || $msgRemoteJidAlt === $remoteJid);

            if ($msgKeyId && ! isset($seen[$msgKeyId]) && $matchesJid) {
                $seen[$msgKeyId] = true;
                $messages[] = $msg;
            }
        }

        if (empty($messages)) {
            // If key.remoteJid filter returned nothing useful, we got an empty conversation — that's fine.
            return ['success' => false, 'data' => ['messages' => []]];
        }

        // Filter locally by timestamp if dates are provided
        if ($startDate || $endDate) {
            $startTs = $startDate ? strtotime($startDate.' 00:00:00') : 0;
            $endTs   = $endDate   ? strtotime($endDate.' 23:59:59')   : time();

            $messages = array_values(array_filter($messages, function ($msg) use ($startTs, $endTs) {
                $timestamp = $msg['messageTimestamp'] ?? 0;

                if (is_array($timestamp) && isset($timestamp['low'])) {
                    $timestamp = $timestamp['low'];
                }

                return $timestamp >= $startTs && $timestamp <= $endTs;
            }));
        }

        $response['data']['messages'] = $messages;

        return $response;
    }

    /**
     * Download media from a message payload and return as Base64.
     */
    public function getBase64FromMediaMessage($instanceName, $messagePayload)
    {
        return $this->request('POST', "/chat/getBase64FromMediaMessage/{$instanceName}", [
            'message' => $messagePayload
        ]);
    }
}
