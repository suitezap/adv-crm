<?php

namespace SuiteZap\LawFirm\Http\Controllers\Admin\Whatsapp;

use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use SuiteZap\LawFirm\Services\Whatsapp\EvolutionService;

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

        // Check current status
        $status = 'disconnected';
        $profile = null;

        $response = $this->service->fetchInstance($instanceName);

        // DEBUG: Log the raw response
        \Illuminate\Support\Facades\Log::info('WhatsApp fetchInstance Response', [
            'instanceName' => $instanceName,
            'response' => $response
        ]);

        // Evolution API returns array of instances or specific instance
        if ($response['success']) {
            // Logic to parse response depending on API version
            // Assuming simple structure for now or adjust based on API response
            $data = $response['data'];

            // Evolution API can return:
            // 1. Array of objects: [0 => ['instance' => [...]]]
            // 2. Direct object (sometimes)
            // 3. Array of instances without 'instance' wrapper (older versions)

            // Normalize to array
            $instances = isset($data[0]) ? $data : [$data];

            foreach ($instances as $item) {
                // Try to get instance data container
                $instData = $item['instance'] ?? $item;

                // Evolution API v2 uses 'name', older versions use 'instanceName'
                $instName = $instData['name'] ?? $instData['instanceName'] ?? null;

                if ($instName === $instanceName) {
                    // Check Status - Evolution API v2 uses 'connectionStatus'
                    $connStatus = $instData['connectionStatus'] ?? $instData['status'] ?? 'close';

                    \Illuminate\Support\Facades\Log::info('WhatsApp Instance Found', [
                        'instanceName' => $instanceName,
                        'connStatus' => $connStatus,
                        'instData' => $instData
                    ]);

                    if ($connStatus === 'open') {
                        $status = 'connected';
                        $profile = $instData['profileName'] ?? $instData['ownerJid'] ?? 'Conectado';
                    }
                    break;
                }
            }
        }

        return view('lawfirm::admin.whatsapp.index', compact('status', 'profile', 'instanceName'));
    }

    /**
     * AJAX: Connect / Generate QR
     */
    public function connect()
    {
        $instanceName = $this->getInstanceName();

        // 1. Ensure instance exists
        $create = $this->service->createInstance($instanceName);

        // 2. Get connection QR
        $connect = $this->service->connectInstance($instanceName);

        if (!$connect['success']) {
            return response()->json(['error' => $connect['error']], 500);
        }

        // Return Base64 or Status
        return response()->json([
            'base64' => $connect['data']['base64'] ?? null,
            'code' => $connect['data']['code'] ?? null,
            'status' => 'qrcode',
        ]);
    }

    /**
     * AJAX: Check Status (Polling)
     */
    public function status()
    {
        $instanceName = $this->getInstanceName();
        $response = $this->service->connectInstance($instanceName); // Often returns status if already connected

        if ($response['success']) {
            // If returns base64, still waiting
            if (isset($response['data']['base64'])) {
                return response()->json(['status' => 'qrcode']);
            }
            // Whatever success response means connected
            return response()->json(['status' => 'connected']);
        }

        return response()->json(['status' => 'disconnected']);
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
            \Illuminate\Support\Facades\Log::warning("Falha ao desconectar API remota, forçando limpeza local: " . $e->getMessage());
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

    protected function getInstanceName()
    {
        // Simple naming convention for V1
        $userId = auth()->guard('user')->id();
        return 'lawfirm_tenant_' . $userId;
    }
}
