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
        $whatsappAssistant = AssistantTemplate::where('slug', 'triagem-whatsapp')
            ->where('is_active', true)
            ->first();

        // Dados da conexão Default (Notificações)
        $instanceNameDefault = $this->getInstanceName('default');
        $statusDefault = 'disconnected';
        $profileDefault = null;
        $isConfiguredDefault = ! empty(MotherShipService::getEvolutionConfig('default')['instance']);

        if ($isConfiguredDefault) {
            $this->service->setConfigType('default');
            $response = $this->service->fetchInstance($instanceNameDefault);
            if ($response['success']) {
                $data = $response['data'];
                $instances = isset($data[0]) ? $data : [$data];
                foreach ($instances as $item) {
                    $instData = $item['instance'] ?? $item;
                    $instName = $instData['name'] ?? $instData['instanceName'] ?? null;
                    $connStatus = $instData['connectionStatus'] ?? $instData['status'] ?? 'close';

                    if ($instName === $instanceNameDefault && $connStatus === 'open') {
                        $statusDefault = 'connected';
                        $profileDefault = $instData['profileName'] ?? $instData['ownerJid'] ?? 'Conectado';
                        break;
                    }
                }
            }
        }

        // Dados da conexão Atendimento
        $instanceNameAtendimento = $this->getInstanceName('atendimento');
        $statusAtendimento = 'disconnected';
        $profileAtendimento = null;
        $isConfiguredAtendimento = ! empty(MotherShipService::getEvolutionConfig('atendimento')['instance']);

        if ($isConfiguredAtendimento) {
            $this->service->setConfigType('atendimento');
            $response = $this->service->fetchInstance($instanceNameAtendimento);
            if ($response['success']) {
                $data = $response['data'];
                $instances = isset($data[0]) ? $data : [$data];
                foreach ($instances as $item) {
                    $instData = $item['instance'] ?? $item;
                    $instName = $instData['name'] ?? $instData['instanceName'] ?? null;
                    $connStatus = $instData['connectionStatus'] ?? $instData['status'] ?? 'close';

                    if ($instName === $instanceNameAtendimento && $connStatus === 'open') {
                        $statusAtendimento = 'connected';
                        $profileAtendimento = $instData['profileName'] ?? $instData['ownerJid'] ?? 'Conectado';
                        break;
                    }
                }
            }
        }

        return view('lawfirm::admin.whatsapp.index', compact(
            'whatsappAssistant',
            'statusDefault', 'profileDefault', 'instanceNameDefault', 'isConfiguredDefault',
            'statusAtendimento', 'profileAtendimento', 'instanceNameAtendimento', 'isConfiguredAtendimento'
        ));
    }

    /**
     * AJAX: Gera QR Code para pareamento.
     */
    public function getQrCode(Request $request)
    {
        $type = $request->input('type', 'default');
        $instanceName = $this->getInstanceName($type);

        $this->service->setConfigType($type);

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
    public function getStatus(Request $request)
    {
        $type = $request->input('type', 'default');
        $config = MotherShipService::getEvolutionConfig($type);

        if (! $config) {
            return response()->json(['success' => false, 'state' => 'unconfigured']);
        }

        $this->service->setConfigType($type);
        $response = $this->service->connectInstance($config['instance']);

        if (! $response || ! $response['success']) {
            return response()->json(['success' => false, 'state' => 'error']);
        }

        $state = $response['data']['instance']['state'] ?? (
            isset($response['data']['base64']) ? 'qrcode' : 'connected'
        );

        return response()->json(['success' => true, 'state' => $state]);
    }

    public function disconnect(Request $request)
    {
        $type = $request->input('type', 'default');
        $instanceName = $this->getInstanceName($type);

        $this->service->setConfigType($type);

        try {
            // Tenta desconectar na API
            $this->service->disconnectInstance($instanceName);
        } catch (\Exception $e) {
            // Apenas loga o erro, mas NÃO PARA a execução.
            \Illuminate\Support\Facades\Log::warning('Falha ao desconectar API remota, forçando limpeza local: '.$e->getMessage());
        }

        // Retornar sucesso para o front-end limpar o estado
        session()->flash('success', 'WhatsApp desconectado com sucesso (Local e Remoto).');

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: Envia uma notificação de teste via WhatsApp. (Admin only)
     *
     * O parâmetro `type` define qual conexão é usada para o disparo de teste:
     *   - 'default'     → Notificações (Padrão) — usada por TODOS os envios automáticos do sistema
     *   - 'atendimento' → Assistente de Atendimento — somente para teste nesta conexão
     *
     * REGRA: Todos os disparos automáticos do sistema SEMPRE usam type='default'.
     * O type='atendimento' só é válido neste endpoint de teste.
     */
    public function testNotification(Request $request)
    {
        $request->validate([
            'phone'   => 'required|string',
            'message' => 'required|string',
            'type'    => 'nullable|string|in:default,atendimento',
        ]);

        $type = $request->input('type', 'default');

        // Garante que a conexão selecionada está configurada
        $config = MotherShipService::getEvolutionConfig($type);
        if (! $config || empty($config['instance'])) {
            return response()->json([
                'success' => false,
                'message' => 'A conexão selecionada não está configurada no MotherShip.',
            ], 503);
        }

        \SuiteZap\LawFirm\Whatsapp\Jobs\SendWhatsappNotification::dispatch(
            $request->phone,
            $request->message,
            [],
            MotherShipService::getTenantId(),
            $type   // Passa o tipo para o Job respeitar a instância correta
        );

        $label = $type === 'atendimento' ? 'Assistente de Atendimento' : 'Notificações (Padrão)';

        return response()->json(['success' => true, 'message' => "Teste agendado com sucesso via {$label}."]);
    }

    /**
     * Resolve o nome da instância Evolution para o tenant atual.
     * Prioridade: MotherShip → config() fallback (dev local).
     */
    protected function getInstanceName(string $type = 'default'): string
    {
        $config = MotherShipService::getEvolutionConfig($type);

        if ($config && ! empty($config['instance'])) {
            return $config['instance'];
        }

        abort(503, 'WhatsApp não configurado no MotherShip para este escritório.');
    }
}
