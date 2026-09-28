<?php

namespace SuiteZap\LawFirm\Legal\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\Legal\Services\AgendaService;
use SuiteZap\LawFirm\Legal\Services\DeadlineService;
use Webkul\Activity\Repositories\ActivityRepository;

class AgendaController extends Controller
{
    public function __construct(
        protected AgendaService $agendaService,
        protected ActivityRepository $activityRepository,
        protected DeadlineService $deadlineService
    ) {}

    /**
     * Renderiza a view da Agenda Jurídica (Launcher).
     */
    public function index()
    {
        abort_if(! bouncer()->hasPermission('lawfirm.agenda.view'), 401, 'This action is unauthorized');

        return view('lawfirm::Legal.agenda.launcher');
    }

    /**
     * Renderiza a view da Agenda Jurídica (FullCalendar).
     * Quando acessada diretamente (submenu Jurídico), exibe com layout CRM completo.
     * Quando ?clean=1 é passado (popup/aba via launcher), exibe sem menus.
     */
    public function viewer()
    {
        abort_if(! bouncer()->hasPermission('lawfirm.agenda.view'), 401, 'This action is unauthorized');

        return view('lawfirm::Legal.agenda.index');
    }

    /**
     * Retorna todos os eventos unificados em formato FullCalendar (JSON).
     */
    public function getEventos(): JsonResponse
    {
        abort_if(! bouncer()->hasPermission('lawfirm.agenda.view'), 401, 'This action is unauthorized');

        $eventos = $this->agendaService->getEventosUnificados();

        return response()->json($eventos);
    }

    /**
     * Atualiza a data de um evento via drag-and-drop.
     */
    public function updateDragDrop(Request $request, int $id): JsonResponse
    {
        abort_if(! bouncer()->hasPermission('lawfirm.agenda.edit'), 401, 'This action is unauthorized');

        $validated = $request->validate([
            'tipo'      => 'required|string|in:activity,prazo',
            'new_start' => 'required|string',
            'new_end'   => 'nullable|string',
        ]);

        $success = $this->agendaService->updateEventDate(
            $validated['tipo'],
            $id,
            $validated['new_start'],
            $validated['new_end'] ?? null
        );

        if (! $success) {
            return response()->json(['error' => 'Evento não encontrado ou sem permissão.'], 404);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Cria uma nova Atividade (compromisso) via modal da Agenda Jurídica.
     *
     * Quando processo_id é fornecido (agenda aberta de dentro de um Processo),
     * cria automaticamente um Prazo vinculado na Gestão de Prazos e Tarefas,
     * com activity_id preenchido para evitar duplicação visual na agenda.
     *
     * Isolamento multi-tenant: Processo::find() usa BelongsToTenant scope,
     * portanto processo de outro tenant retorna null e nenhum Prazo é criado.
     */
    public function storeActivity(Request $request): JsonResponse
    {
        abort_if(! bouncer()->hasPermission('lawfirm.agenda.create'), 401, 'This action is unauthorized');

        $validated = $request->validate([
            'titulo'                 => 'required|string|max:255',
            'tipo'                   => 'required|string|in:call,meeting,lunch,email',
            'descricao'              => 'nullable|string|max:2000',
            'data_inicio'            => 'required|string',
            'data_fim'               => 'nullable|string',
            'is_done'                => 'nullable|boolean',
            'lead_id'                => 'nullable|integer|exists:leads,id',
            'processo_id'            => 'nullable|integer',
            'participants'           => 'nullable|array',
            'participants.users'     => 'nullable|array',
            'participants.users.*'   => 'integer',
            'participants.persons'   => 'nullable|array',
            'participants.persons.*' => 'integer',
        ]);

        $userId = auth()->guard('user')->id();

        $start = Carbon::parse($validated['data_inicio']);
        $end = isset($validated['data_fim']) && ! empty($validated['data_fim'])
            ? Carbon::parse($validated['data_fim'])
            : $start->copy()->addHour();

        $activity = $this->activityRepository->create([
            'type'          => $validated['tipo'],
            'title'         => $validated['titulo'],
            'comment'       => $validated['descricao'] ?? '',
            'schedule_from' => $start->format('Y-m-d H:i:s'),
            'schedule_to'   => $end->format('Y-m-d H:i:s'),
            'is_done'       => $validated['is_done'] ?? false ? 1 : 0,
            'user_id'       => $userId,
            'participants'  => $validated['participants'] ?? [],
        ]);

        // Vincular Lead (Oportunidade) à Atividade do Krayin
        if (! empty($validated['lead_id'])) {
            $activity->leads()->syncWithoutDetaching([$validated['lead_id']]);
        }

        // Quando aberto de dentro de um Processo: cria Prazo vinculado na Gestão de Prazos
        if (! empty($validated['processo_id'])) {
            // BelongsToTenant garante que só encontra processo do tenant atual
            $processo = Processo::find($validated['processo_id']);

            if ($processo) {
                // Mapear tipo de atividade para tipo de prazo
                $prazoTipoMap = [
                    'call'    => 'tarefa',
                    'meeting' => 'prazo',
                    'lunch'   => 'prazo',
                    'email'   => 'tarefa',
                ];

                $this->deadlineService->createDeadline([
                    'processo_id'     => $processo->id,
                    'titulo'          => $validated['titulo'],
                    'descricao'       => $validated['descricao'] ?? null,
                    'data_vencimento' => $start->format('Y-m-d H:i:s'),
                    'tipo'            => $prazoTipoMap[$validated['tipo']] ?? 'prazo',
                    'activity_id'     => $activity->id,
                ]);
            }
        }

        return response()->json(['success' => true, 'activity_id' => $activity->id]);
    }
}
