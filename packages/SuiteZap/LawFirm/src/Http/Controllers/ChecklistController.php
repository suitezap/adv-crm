<?php

namespace SuiteZap\LawFirm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SuiteZap\LawFirm\Models\CaseChecklist;
use SuiteZap\LawFirm\Services\ChecklistTemplates;
use Webkul\Lead\Repositories\LeadRepository;

class ChecklistController extends Controller
{
    protected $leadRepository;

    public function __construct(LeadRepository $leadRepository)
    {
        $this->leadRepository = $leadRepository;
    }

    /**
     * Retorna o estado atual do checklist para um Lead específico.
     * Se não existir, retorna status 'new_lead' para o frontend mostrar seleção de área.
     */
    public function show($leadId)
    {
        $checklist = CaseChecklist::where('lead_id', $leadId)->first();

        // Retrieve Lead to check status
        $lead = $this->leadRepository->find($leadId);

        // Safe check using optional() to prevent 500 if stage is null
        $isWon = optional($lead->stage)->code === 'won';
        $leadStatusLabel = optional($lead->stage)->name ?? 'Unknown';

        // Fetch Viability Template for AI Analysis in Pre-Screening
        $viabilityTemplate = \SuiteZap\LawFirm\Models\AssistantTemplate::on('mothership')
            ->where('id', 1)
            ->first();

        // Se não existe, retornar indicação para o frontend mostrar seleção de área
        if (!$checklist) {
            return response()->json([
                'status' => 'new_lead',
                'available_types' => ChecklistTemplates::getAvailableTypes(),
                'is_won' => $isWon,
                'lead_status_label' => $leadStatusLabel,
                'viability_template' => $viabilityTemplate,
            ]);
        }

        // Se existe, retornar dados + template correspondente
        return response()->json([
            'status' => 'success',
            'data' => $checklist,
            'steps' => ChecklistTemplates::getTemplate($checklist->type),
            'is_won' => $isWon,
            'lead_status_label' => $leadStatusLabel,
            'viability_template' => $viabilityTemplate,
        ]);
    }

    /**
     * Inicializa o checklist com o tipo selecionado pelo usuário.
     */
    public function initialize(Request $request, $leadId)
    {
        $request->validate([
            'type' => 'required|string|in:labor_claimant,family_divorce,civil_general',
        ]);

        $type = $request->input('type');
        $steps = ChecklistTemplates::getTemplate($type);

        // Verificar se já existe (evitar duplicatas)
        $existing = CaseChecklist::where('lead_id', $leadId)->first();
        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Checklist já existe para este Lead.',
            ], 400);
        }

        // Criar o registro
        $checklist = CaseChecklist::create([
            'lead_id' => $leadId,
            'type' => $type,
            'current_step' => 1,
            'step_data' => [],
            'status' => 'draft',
            'created_by' => auth()->guard('user')->id(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Checklist inicializado com sucesso.',
            'data' => $checklist,
            'steps' => $steps,
        ]);
    }

    /**
     * Salva o progresso de uma etapa.
     */
    public function saveProgress(Request $request, $leadId)
    {
        $checklist = CaseChecklist::where('lead_id', $leadId)->firstOrFail();

        $step = $request->input('step');
        $data = $request->input('data');
        $isCompleted = $request->input('completed', false);

        // Obter número total de passos do template
        $steps = ChecklistTemplates::getTemplate($checklist->type);
        $totalSteps = count($steps);

        // Atualiza os dados da etapa
        if ($step && $data) {
            $checklist->updateStepData((int) $step, $data);
        }

        // Se completou, avança o ponteiro ou finaliza
        $nextStep = $step + 1;

        if ($isCompleted) {
            // Se completou passo < totalSteps, vai para o próximo
            if ($step < $totalSteps && $checklist->current_step == $step) {
                $checklist->current_step = $nextStep;
                $checklist->status = 'in_progress';
            }
            // Se completou último passo, marca checklist como concluído
            elseif ($step == $totalSteps) {
                $checklist->status = 'completed';
            }
        }

        // Se NÃO completou (aka Reabrir Checklist), volta status para in_progress
        // O front envia 'completed': false ao reabrir.
        if ($isCompleted === false && $checklist->status === 'completed') {
            $checklist->status = 'in_progress';
        }

        $checklist->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Progresso salvo com sucesso.',
            'next_step' => $checklist->current_step,
            'current_data' => $checklist->step_data
        ]);
    }

    /**
     * Ponto de entrada para a validação via IA.
     * Stub para integração futura com n8n/MotherShipService.
     */
    public function validateWithAi(Request $request, $leadId)
    {
        // TODO: Implementar chamada real ao MotherShipService -> n8n

        // Mock de resposta para desenvolvimento do Frontend
        return response()->json([
            'status' => 'ok',
            'ai_feedback' => [
                'risco' => 'medio',
                'pontos_atencao' => [
                    'Verificar prescrição bienal (data de saída vs hoje)',
                    'Falta documento RG ou CNH legível'
                ],
                'mensagem' => 'A análise preliminar indica risco de prescrição se a ação não for ajuizada em 30 dias.'
            ]
        ]);
    }

    /**
     * Execute AI Template Analysis (Pre-Screening)
     * Receives template_id and form data, processes with AI (stub for now).
     */
    public function executeAi(Request $request, $leadId)
    {
        $request->validate([
            'template_id' => 'required|integer',
            'data' => 'required|array',
        ]);

        $templateId = $request->input('template_id');
        $formData = $request->input('data');

        // Fetch template from mothership
        $template = \SuiteZap\LawFirm\Models\AssistantTemplate::on('mothership')
            ->where('id', $templateId)
            ->first();

        if (!$template) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template não encontrado.',
            ], 404);
        }

        // Log for debugging (URL construction simulation)
        \Log::info('AI Template Execution', [
            'lead_id' => $leadId,
            'template_id' => $templateId,
            'template_slug' => $template->slug ?? 'N/A',
            'form_data' => $formData,
            'webhook_url' => $template->n8n_webhook_url ?? 'N/A',
        ]);

        // TODO: Real integration with n8n/MotherShipService
        // For now, return mock success response
        return response()->json([
            'status' => 'success',
            'message' => 'Solicitação enviada para IA',
            'mock_response' => 'Análise simulada: Risco Médio. Recomenda-se verificar documentação antes de prosseguir.',
            'template_used' => $template->title ?? 'Template de Triagem',
        ]);
    }
}
