<?php

namespace SuiteZap\LawFirm\Legal\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SuiteZap\LawFirm\Legal\Services\AgendaService;
use Webkul\Activity\Repositories\ActivityRepository;

class AgendaController extends Controller
{
    public function __construct(
        protected AgendaService $agendaService,
        protected ActivityRepository $activityRepository
    ) {}

    /**
     * Renderiza a view da Agenda Jurídica (FullCalendar).
     */
    public function index()
    {
        return view('lawfirm::Legal.agenda.index');
    }

    /**
     * Retorna todos os eventos unificados em formato FullCalendar (JSON).
     */
    public function getEventos(): JsonResponse
    {
        $eventos = $this->agendaService->getEventosUnificados();

        return response()->json($eventos);
    }

    /**
     * Atualiza a data de um evento via drag-and-drop.
     */
    public function updateDragDrop(Request $request, int $id): JsonResponse
    {
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
     * Utiliza o ActivityRepository do Krayin para garantir compatibilidade
     * com todos os módulos do sistema (Activities, Calendário, etc.)
     */
    public function storeActivity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titulo'                 => 'required|string|max:255',
            'tipo'                   => 'required|string|in:call,meeting,lunch,email',
            'descricao'              => 'nullable|string|max:2000',
            'data_inicio'            => 'required|string',
            'data_fim'               => 'nullable|string',
            'is_done'                => 'nullable|boolean',
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

        return response()->json(['success' => true, 'activity_id' => $activity->id]);
    }
}
