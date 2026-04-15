<?php

namespace SuiteZap\LawFirm\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use SuiteZap\LawFirm\Whatsapp\Services\EvolutionService;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

class WhatsappController extends Controller
{
    protected $evolutionService;

    public function __construct(EvolutionService $evolutionService)
    {
        $this->evolutionService = $evolutionService;
    }

    /**
     * Exibe a página de configurações e conexão do WhatsApp.
     */
    public function index()
    {
        $config = MotherShipService::getEvolutionConfig();
        
        $isConfigured = !empty($config['base_url']) && !empty($config['instance']) && !empty($config['token']);

        return view('lawfirm::admin.whatsapp.index', [
            'isConfigured' => $isConfigured,
            'instanceName' => $config['instance'] ?? null
        ]);
    }

    /**
     * Retorna o QR Code para pareamento.
     */
    public function getQrCode()
    {
        $config = MotherShipService::getEvolutionConfig();

        if (!$config) {
            return response()->json(['success' => false, 'message' => 'Evolution não está configurado para este Tenant.'], 503);
        }

        $instanceName = $config['instance'];

        // Tenta buscar o status atual da instância
        $fetchStatus = $this->evolutionService->fetchInstance($instanceName);

        // Se a instância não existe, tenta criar
        if (
            (isset($fetchStatus['error']) && str_contains(strtolower($fetchStatus['error']), 'not found')) ||
            (isset($fetchStatus['success']) && $fetchStatus['success'] === true && empty($fetchStatus['data']))
        ) {
            $create = $this->evolutionService->createInstance($instanceName);
            
            if (!isset($create['success']) || !$create['success']) {
                \Illuminate\Support\Facades\Log::error('Evolution API Create Error:', ['payload' => $create, 'config' => $config]);
                return response()->json([
                    'success' => false, 
                    'message' => 'Erro ao criar instância na Evolution API.', 
                    'details' => $create,
                    'config_used' => [
                        'url' => $config['base_url'],
                        'instance' => $config['instance']
                    ]
                ], 500);
            }
            
            // createInstance retorna o qrcode no objeto:
            $data = $create['data'] ?? [];
            return response()->json([
                'success' => true,
                'state'   => $data['instance']['state'] ?? $data['instance']['connectionStatus'] ?? 'unknown',
                'qrcode'  => $data['qrcode']['base64'] ?? null
            ]);
        }

        // Recupera o QR Code de conexão
        $response = $this->evolutionService->connectInstance($instanceName);

        if (!$response || !isset($response['success']) || !$response['success']) {
            \Illuminate\Support\Facades\Log::error('Evolution API Connect Error:', ['payload' => $response, 'config' => $config]);
            return response()->json([
                'success' => false, 
                'message' => 'Erro ao comunicar com a Evolution API.', 
                'details' => $response,
                'config_used' => [
                    'url' => $config['base_url'],
                    'instance' => $config['instance']
                ]
            ], 500);
        }

        $data = $response['data'];
        
        // Na Evolution v2, se já estiver conectado, a API devolve o objeto instance com status 'open'.
        // Caso precise gerar o QR code, devolve apenas o json com 'base64'.
        $actualState = $data['instance']['state'] ?? $data['instance']['connectionStatus'] ?? 'connecting';

        return response()->json([
            'success' => true,
            'state'   => $actualState, 
            'qrcode'  => $data['base64'] ?? null
        ]);
    }

    /**
     * Verifica o status da conexão atual.
     */
    public function getStatus()
    {
        $config = MotherShipService::getEvolutionConfig();

        if (!$config) {
            return response()->json(['success' => false, 'state' => 'unconfigured']);
        }

        $response = $this->evolutionService->connectInstance($config['instance']);

        if (!$response || !isset($response['success']) || !$response['success']) {
            return response()->json(['success' => false, 'state' => 'error']);
        }

        return response()->json([
            'success' => true,
            'state'   => $response['data']['instance']['state'] ?? 'unknown'
        ]);
    }

    /**
     * Desconecta o WhatsApp atual.
     */
    public function disconnect()
    {
        $config = MotherShipService::getEvolutionConfig();

        if (!$config) {
            return response()->json(['success' => false, 'message' => 'Evolution não configurado.']);
        }

        try {
            $this->evolutionService->disconnectInstance($config['instance']);
            return response()->json(['success' => true, 'message' => 'WhatsApp desconectado com sucesso.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao desconectar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Testa o disparo de uma notificação (Apenas via Admin UI).
     */
    public function testNotification(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string'
        ]);

        \SuiteZap\LawFirm\Whatsapp\Jobs\SendWhatsappNotification::dispatch(
            $request->phone, 
            $request->message,
            [],
            MotherShipService::getTenantId()
        );

        return response()->json(['success' => true, 'message' => 'Notificação de teste agendada com sucesso.']);
    }
}
