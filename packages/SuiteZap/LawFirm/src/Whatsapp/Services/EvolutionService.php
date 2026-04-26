<?php

namespace SuiteZap\LawFirm\Whatsapp\Services;

use GuzzleHttp\Client;
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
        // Tenta carregar do Banco de Dados (MotherShip)
        $config = MotherShipService::getEvolutionConfig();

        if ($config) {
            $this->baseUrl = $config['base_url'];
            $this->apiKey = $config['token'];
            $this->instanceName = $config['instance'];
        } else {
            // 2. Fallback para .env (Legado ou Debug)
            $this->baseUrl = config('lawfirm.evolution.api_url')
                ?: core()->getConfigData('lawfirm.settings.general.evolution_api_url');

            $this->apiKey = config('lawfirm.evolution.api_key')
                ?: core()->getConfigData('lawfirm.settings.general.evolution_api_key');

            $this->instanceName = config('lawfirm.evolution.instance_name');
        }

        // Normalize URL
        $this->baseUrl = rtrim($this->baseUrl, '/');

        if ($this->baseUrl && $this->apiKey) {
            $this->client = new Client([
                'base_uri' => $this->baseUrl,
                'headers' => [
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 30,
            ]);
        }
    }

    /**
     * Helper to handle Guzzle requests nicely
     */
    protected function request($method, $uri, $data = [])
    {
        if (!$this->client) {
            return [
                'success' => false,
                'error' => 'Evolution API não configurada (URL ou Key ausentes).'
            ];
        }

        try {
            $options = [];
            if (!empty($data)) {
                $options['json'] = $data;
            }


            $response = $this->client->request($method, $uri, $options);
            $body = json_decode($response->getBody(), true);

            return ['success' => true, 'data' => $body];

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // 4xx errors
            $response = $e->getResponse();
            $body = json_decode($response->getBody(), true);

            return [
                'success' => false,
                'error' => $body['message'] ?? $body['error'] ?? $e->getMessage()
            ];

        } catch (\Exception $e) {
            // Other errors
            Log::error('Evolution API Error: ' . $e->getMessage());
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
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
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
        if (!$this->client) {
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
            Log::error("Erro ao deletar instância Evolution: " . $e->getMessage());
            throw $e; // Re-throw to let controller handle it
        }
    }

    /**
     * Envia mensagem de texto
     */
    public function sendMessage($instanceName, $number, $text)
    {
        return $this->request('POST', "/message/sendText/{$instanceName}", [
            'number' => $number,
            'text' => $text,
            'delay' => 1200,
            'linkPreview' => false
        ]);
    }

    /**
     * Busca histórico de mensagens de um contato e filtra as relativas a um intervalo de datas (localmente).
     */
    public function fetchMessagesByDateRange($instanceName, $remoteJid, $startDate = null, $endDate = null, $limit = 500)
    {
        $response = $this->request('POST', "/chat/findMessages/{$instanceName}", [
            'where' => [
                'key' => [
                    'remoteJid' => $remoteJid
                ]
            ],
            // Request more messages depending on the necessity, since limits might truncate past dates.
            // A higher limit ensures we go back in time, but the API has standard pagination limits usually.
            'limit' => (int) $limit
        ]);

        if (!$response['success'] || empty($response['data']['messages'])) {
            return $response;
        }
        
        $messages = $response['data']['messages']['records'] ?? $response['data']['messages'] ?? [];
        
        // Filter locally by timestamp if dates are provided
        if ($startDate || $endDate) {
            $startTs = $startDate ? strtotime($startDate . ' 00:00:00') : 0;
            $endTs = $endDate ? strtotime($endDate . ' 23:59:59') : time();
            
            $filteredMessages = array_filter($messages, function($msg) use ($startTs, $endTs) {
                // Evolution API returns messageTimestamp as either a Unix timestamp directly or object.
                // Assuming it's a Unix timestamp as standard Baileys response.
                $timestamp = $msg['messageTimestamp'] ?? 0;
                
                if (is_array($timestamp) && isset($timestamp['low'])) {
                     $timestamp = $timestamp['low']; // handle Long timestamps (int64 structure)
                }

                return $timestamp >= $startTs && $timestamp <= $endTs;
            });
            
            // Re-index array
            $response['data']['messages'] = array_values($filteredMessages);
        }

        return $response;
    }
}
