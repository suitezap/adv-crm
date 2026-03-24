<?php

namespace SuiteZap\LawFirm\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nService
{
    /**
     * Envia os dados para o Webhook do n8n e aguarda resposta.
     *
     * @param string $url URL do Webhook
     * @param array $payload Dados do formulário
     * @return string Resposta da IA ou erro
     */
    public function executeWebhook($url, $payload)
    {
        try {
            // Aumenta timeout para 60s pois IA pode demorar
            $response = Http::timeout(60)->post($url, $payload);

            if ($response->successful()) {
                // Tenta pegar o campo 'output' ou 'text' do JSON, ou retorna o corpo todo
                $json = $response->json();
                return $json['output'] ?? $json['text'] ?? $json['message'] ?? $response->body();
            }

            Log::error("Erro n8n [{$url}]: " . $response->status() . " - " . $response->body());
            return "Erro N8N (" . $response->status() . "): " . ($response->json()['message'] ?? 'Falha remota.');

        } catch (\Exception $e) {
            Log::error("Exceção n8n [{$url}]: " . $e->getMessage());
            return "Falha de Conexão: " . $e->getMessage();
        }
    }
}
