<?php

namespace SuiteZap\LawFirm\GED\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SuiteZap\LawFirm\GED\Models\ProcessDocument;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\SaaS\Services\SaasFileService;

/**
 * DocumentChecklistApiController
 *
 * Gerencia documentos do checklist de um processo via API.
 * C2 fix (2026-05-15): Refatorado para usar SaasFileService em vez de Storage:: direto.
 * Regra 2.2 do SKILL.md: Storage:: é proibido fora do SaasFileService.
 */
class DocumentChecklistApiController extends Controller
{
    public function __construct(protected SaasFileService $fileService) {}

    // GET /api/lawfirm/documents/{processId} → Lista documentos do processo
    public function index($processId)
    {
        $documents = ProcessDocument::where('processo_id', $processId)->get();

        return response()->json(['data' => $documents]);
    }

    // PUT /api/lawfirm/documents/{id} → Atualiza status/notas (Webhook do WhatsApp)
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,received,approved,rejected',
            'notes'  => 'nullable|string',
        ]);

        $document = ProcessDocument::findOrFail($id);
        $document->update($request->only(['status', 'notes']));

        return response()->json(['message' => 'Atualizado com sucesso', 'data' => $document]);
    }

    /**
     * POST /api/lawfirm/documents/{id}/upload
     * Upload de arquivo para documento do checklist.
     * Usa SaasFileService para garantir isolamento multi-tenant do bucket S3/MinIO.
     */
    public function uploadFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480', // 20MB
        ]);

        $document = ProcessDocument::findOrFail($id);

        // ✅ CORRETO: usa SaasFileService::store() — respeita isolamento do bucket por tenant
        $path = $this->fileService->store(
            $request->file('file'),
            'checklist/'.$document->processo_id
        );

        $document->update([
            'file_path' => $path,
            'status'    => 'received',
        ]);

        // ✅ CORRETO: usa SaasFileService::url() — retorna URL pública/assinada correta por tenant
        return response()->json([
            'message'  => 'Arquivo enviado com sucesso',
            'data'     => $document,
            'file_url' => $this->fileService->url($path),
        ]);
    }
}
