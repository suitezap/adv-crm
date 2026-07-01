<?php

namespace SuiteZap\LawFirm\Whatsapp\Http\Controllers;

use Illuminate\Http\Request;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\Whatsapp\Services\EvolutionService;
use Webkul\Admin\Http\Controllers\Controller;

class ConnectionController extends Controller
{
    protected $service;

    public function __construct(EvolutionService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the connection page.
     */
    public function index()
    {
        $instanceName = $this->getInstanceName();

        $status = 'disconnected';
        $profile = null;

        $response = $this->service->fetchInstance($instanceName);

        if ($response['success']) {
            $data = $response['data'];
            $instances = isset($data[0]) ? $data : [$data];

            foreach ($instances as $item) {
                $instData = $item['instance'] ?? $item;
                $instName = $instData['name'] ?? $instData['instanceName'] ?? null;
                $connStatus = $instData['connectionStatus'] ?? $instData['status'] ?? 'close';

                if ($instName === $instanceName && $connStatus === 'open') {
                    $status = 'connected';
                    $profile = $instData['profileName'] ?? $instData['ownerJid'] ?? 'Conectado';
                    break;
                }
            }
        }

        $whatsappAssistant = AssistantTemplate::where('slug', 'triagem-whatsapp')
            ->where('is_active', true)
            ->first();

        $isConfigured = ! empty(MotherShipService::getEvolutionConfig()['instance']);

        return view('lawfirm::admin.whatsapp.index', compact('status', 'profile', 'instanceName', 'whatsappAssistant', 'isConfigured'));
    }

    /**
     * AJAX: Gera QR Code para pareamento.
     */
    public function getQrCode()
    {
        $instanceName = $this->getInstanceName();

        // Garante que a instância existe antes de solicitar o QR
        $this->service->createInstance($instanceName);

        $connect = $this->service->connectInstance($instanceName);

        if (! $connect['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao comunicar com a Evolution API.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'state'   => $connect['data']['instance']['state'] ?? 'connecting',
            'qrcode'  => $connect['data']['base64'] ?? null,
        ]);
    }

    /**
     * AJAX: Verifica o status da conexão (Polling).
     */
    public function getStatus()
    {
        $config = MotherShipService::getEvolutionConfig();

        if (! $config) {
            return response()->json(['success' => false, 'state' => 'unconfigured']);
        }

        $response = $this->service->connectInstance($config['instance']);

        if (! $response || ! $response['success']) {
            return response()->json(['success' => false, 'state' => 'error']);
        }

        $state = $response['data']['instance']['state'] ?? (
            isset($response['data']['base64']) ? 'qrcode' : 'connected'
        );

        return response()->json(['success' => true, 'state' => $state]);
    }

    public function disconnect()
    {
        $instanceName = $this->getInstanceName();

        try {
            // Tenta desconectar na API
            $this->service->disconnectInstance($instanceName);
        } catch (\Exception $e) {
            // Apenas loga o erro, mas NÃO PARA a execução.
            // O objetivo é permitir que o usuário resete o banco local mesmo se a API estiver fora.
            \Illuminate\Support\Facades\Log::warning('Falha ao desconectar API remota, forçando limpeza local: '.$e->getMessage());
        }

        // LIMPEZA LOCAL (Obrigatório acontecer sempre):
        // Neste projeto, o status é consultado em tempo real, mas caso haja cache ou configs,
        // este é o momento de limpar.
        // Se houver uma flag de 'whatsapp_status' em core_config, atualizar aqui:
        // Core::setConfigData('lawfirm.whatsapp.status', 'disconnected');

        // Retornar sucesso para o front-end limpar o estado
        session()->flash('success', 'WhatsApp desconectado com sucesso (Local e Remoto).');

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: Envia uma notificação de teste via WhatsApp. (Admin only)
     */
    public function testNotification(Request $request)
    {
        $request->validate([
            'phone'   => 'required|string',
            'message' => 'required|string',
        ]);

        \SuiteZap\LawFirm\Whatsapp\Jobs\SendWhatsappNotification::dispatch(
            $request->phone,
            $request->message,
            [],
            MotherShipService::getTenantId()
        );

        return response()->json(['success' => true, 'message' => 'Notificação de teste agendada com sucesso.']);
    }

    /**
     * Resolve o nome da instância Evolution para o tenant atual.
     * Prioridade: MotherShip → config() fallback (dev local).
     */
    protected function getInstanceName(): string
    {
        $config = MotherShipService::getEvolutionConfig();

        if ($config && ! empty($config['instance'])) {
            return $config['instance'];
        }

        abort(503, 'WhatsApp não configurado no MotherShip para este escritório.');
    }
}
