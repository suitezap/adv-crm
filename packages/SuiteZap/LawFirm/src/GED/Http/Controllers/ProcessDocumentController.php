<?php

namespace SuiteZap\LawFirm\GED\Http\Controllers;

use Illuminate\Http\Request;
use SuiteZap\LawFirm\GED\Services\DocumentService;
use SuiteZap\LawFirm\Models\Processo;
use Webkul\Admin\Http\Controllers\Controller;

class ProcessDocumentController extends Controller
{
    protected $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'processo_id' => 'required|exists:processos,id',
            'anexos.*' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:20480', // 20MB Max
        ], [
            'anexos.*.mimes' => 'Apenas arquivos PDF, Image (JPG/PNG) e Word (DOC/DOCX) são permitidos.',
            'anexos.*.max' => 'O tamanho máximo do arquivo é 20MB.',
        ]);

        try {
            $processo = Processo::findOrFail($request->input('processo_id'));

            if ($request->hasFile('anexos')) {
                foreach ($request->file('anexos') as $file) {
                    $this->documentService->storeFile($file, $processo);
                }
            }

            if ($request->ajax()) {
                return response()->json(['message' => 'Documentos enviados com sucesso.', 'status' => 'success']);
            }

            return redirect()->back()->with('success', 'Documentos enviados com sucesso.');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage(), 'status' => 'error'], 400);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $this->documentService->deleteFile($id);

            if (request()->ajax()) {
                return response()->json(['message' => 'Anexo excluído com sucesso.', 'status' => 'success']);
            }

            return redirect()->back()->with('success', 'Anexo excluído com sucesso.');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['message' => 'Erro ao excluir anexo.', 'status' => 'error'], 500);
            }
            return redirect()->back()->with('error', 'Erro ao excluir anexo.');
        }
    }
}
