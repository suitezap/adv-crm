<?php

namespace SuiteZap\LawFirm\Whatsapp\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\Legal\Models\WhatsappImport;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\Whatsapp\Jobs\ImportProcessoWhatsappMessages;

class WhatsappImportController extends Controller
{
    /**
     * Dispatch the asynchronous job to import messages.
     */
    public function dispatchImport(Request $request, $processoId)
    {
        try {
            $request->validate([
                'remote_jid' => 'required|string',
                'start_date' => 'nullable|date',
                'end_date'   => 'nullable|date|after_or_equal:start_date',
            ]);

            $processo = Processo::findOrFail($processoId);

            $userId = auth()->id();
            $tenantId = MotherShipService::getTenantId();

            $remoteJid = preg_replace('/\D/', '', $request->input('remote_jid'));

            if (strpos($remoteJid, '55') !== 0 && strlen($remoteJid) >= 10) {
                $remoteJid = '55'.$remoteJid;
            }
            if (strpos($remoteJid, '@s.whatsapp.net') === false) {
                $remoteJid .= '@s.whatsapp.net';
            }

            ImportProcessoWhatsappMessages::dispatch(
                $processoId,
                $remoteJid,
                $request->input('start_date'),
                $request->input('end_date'),
                $userId,
                $tenantId
            );

            return response()->json([
                'success' => true,
                'message' => 'Importação agendada com sucesso. Você será notificado no WhatsApp ao final do processo.',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Erro de validação: '.collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage().' no arquivo '.$e->getFile().' linha '.$e->getLine(),
            ], 500);
        }
    }

    /**
     * List all import sessions for a Processo (JSON).
     */
    public function listImports($processoId)
    {
        $processo = Processo::findOrFail($processoId);

        $imports = $processo->whatsappImports()
            ->select('id', 'remote_jid', 'contact_name', 'start_date', 'end_date', 'message_count', 'status', 'created_at')
            ->get()
            ->map(function ($import) {
                return [
                    'id'            => $import->id,
                    'contact_name'  => $import->contact_name ?: $import->displayPhone(),
                    'period'        => $import->formattedPeriod(),
                    'message_count' => $import->message_count,
                    'status'        => $import->status,
                    'created_at'    => $import->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'imports' => $imports,
        ]);
    }

    /**
     * Fetch messages and render the chat viewer for a Processo.
     * Supports optional ?import_id=X to filter by a specific import session.
     */
    public function fetchMessages(Request $request, $processoId)
    {
        $processo = Processo::findOrFail($processoId);

        $query = $processo->whatsappMessages();

        $importId = $request->query('import_id');
        if ($importId) {
            $query->where('import_id', $importId);
        }

        $messages = $query->get();

        $html = view('lawfirm::admin.processos.modals.whatsapp-chat-viewer', compact('processo', 'messages'))->render();

        return response()->json([
            'success' => true,
            'html'    => $html,
        ]);
    }

    /**
     * Delete an import session and all its associated messages.
     */
    public function deleteImport($processoId, $importId)
    {
        try {
            $processo = Processo::findOrFail($processoId);

            $import = WhatsappImport::where('id', $importId)
                ->where('processo_id', $processoId)
                ->firstOrFail();

            // Delete associated messages first (safety net if FK cascade fails)
            $import->messages()->delete();
            $import->delete();

            return response()->json([
                'success' => true,
                'message' => 'Importação removida com sucesso.',
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Importação não encontrada.',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Erro ao remover: '.$e->getMessage(),
            ], 500);
        }
    }
}
