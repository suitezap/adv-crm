<?php

namespace SuiteZap\LawFirm\Legal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Legal\Services\DeadlineService;

class DeadlineController extends Controller
{
    protected $deadlineService;

    public function __construct(DeadlineService $deadlineService)
    {
        $this->deadlineService = $deadlineService;
    }

    /**
     * Store a newly created deadline.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'processo_id' => 'required|exists:processos,id',
            'titulo' => 'required|string|max:255',
            'data_vencimento' => 'required|date',
            'tipo' => 'required|in:prazo,tarefa',
            'descricao' => 'nullable|string',
            'activity_id' => 'nullable|integer',
        ]);

        try {
            $prazo = $this->deadlineService->createDeadline($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Prazo criado com sucesso!',
                    'data' => $prazo
                ]);
            }

            session()->flash('success', 'Prazo criado com sucesso!');
            return back();

        } catch (\Exception $e) {
            Log::error("DeadlineController: Error creating deadline: " . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao criar prazo: ' . $e->getMessage()
                ], 500);
            }

            session()->flash('error', 'Erro ao criar prazo: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Update the specified deadline.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // Normalize status to canonical lowercase before validation
        // Accepts 'Concluído', 'concluído', 'Pendente' etc. from form selects
        if ($request->has('status')) {
            $request->merge([
                'status' => strtolower(str_replace(['í', 'Í'], 'i', $request->input('status'))),
            ]);
        }

        $validated = $request->validate([
            'titulo'          => 'sometimes|required|string|max:255',
            'data_vencimento' => 'sometimes|required|date',
            'tipo'            => 'sometimes|required|in:prazo,tarefa',
            'status'          => 'sometimes|required|in:pendente,concluido',
            'descricao'       => 'nullable|string',
        ]);

        try {
            $prazo = $this->deadlineService->updateDeadline($id, $validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Prazo atualizado com sucesso!',
                    'data' => $prazo
                ]);
            }

            session()->flash('success', 'Prazo atualizado com sucesso!');
            return back();

        } catch (\Exception $e) {
            Log::error("DeadlineController: Error updating deadline {$id}: " . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao atualizar prazo: ' . $e->getMessage()
                ], 500);
            }

            session()->flash('error', 'Erro ao atualizar prazo: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Remove the specified deadline.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->deadlineService->deleteDeadline($id);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Prazo excluído com sucesso!'
                ]);
            }

            session()->flash('success', 'Prazo excluído com sucesso!');
            return back();

        } catch (\Exception $e) {
            Log::error("DeadlineController: Error deleting deadline {$id}: " . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao excluir prazo: ' . $e->getMessage()
                ], 500);
            }

            session()->flash('error', 'Erro ao excluir prazo: ' . $e->getMessage());
            return back();
        }
    }

    /**
     * Toggle the status of the specified deadline.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Request $request, $id)
    {
        try {
            $prazo = $this->deadlineService->toggleStatus($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Status alterado com sucesso!',
                'data' => [
                    'status' => $prazo->status,
                    'concluido_em' => $prazo->concluido_em
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("DeadlineController: Error toggling deadline {$id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao alterar status: ' . $e->getMessage()
            ], 500);
        }
    }
}
