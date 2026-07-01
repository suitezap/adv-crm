<?php

namespace SuiteZap\LawFirm\Legal\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SuiteZap\LawFirm\GED\Models\ProcessDocument;
use SuiteZap\LawFirm\Legal\Models\DocumentTemplate;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\Legal\Repositories\DocumentTemplateRepository;
use SuiteZap\LawFirm\Legal\Services\DocumentTemplateService;
use SuiteZap\LawFirm\SaaS\Services\SaasFileService;

class DocumentTemplateController extends Controller
{
    protected DocumentTemplateRepository $repository;

    protected DocumentTemplateService $service;

    protected SaasFileService $fileService;

    public function __construct(
        DocumentTemplateRepository $repository,
        DocumentTemplateService $service,
        SaasFileService $fileService
    ) {
        $this->repository = $repository;
        $this->service = $service;
        $this->fileService = $fileService;
    }

    // =========================================================================
    // CRUD — Manages local templates only
    // =========================================================================

    /**
     * Display a listing of all templates (local + global).
     * Global templates are shown as read-only so the user understands the distinction.
     */
    public function manage()
    {
        // Garante que o cabeçalho e rodapé existam no banco local
        $this->getLayoutTemplates();

        $allTemplates = $this->repository->allActive();
        $localTemplates = DocumentTemplate::orderBy('titulo')->get();

        return view('lawfirm::Legal.modelos.index', compact('allTemplates', 'localTemplates'));
    }

    /**
     * Show the form for creating a new local template.
     */
    public function create()
    {
        return view('lawfirm::Legal.modelos.create');
    }

    /**
     * Store a newly created local template.
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo'   => 'required|string|max:255',
            'tipo'     => 'required|string|max:50',
            'conteudo' => 'required|string',
        ]);

        DocumentTemplate::create([
            'titulo'       => $request->titulo,
            'tipo'         => $request->tipo,
            'area_direito' => $request->area_direito,
            'conteudo'     => $request->conteudo,
            'descricao'    => $request->descricao,
            'ativo'        => $request->boolean('ativo', true),
            'user_id'      => auth()->guard('user')->user()->id,
        ]);

        session()->flash('success', 'Modelo de documento criado com sucesso.');

        return redirect()->route('admin.modelos.index');
    }

    /**
     * Show the form for editing a local template.
     * Global templates (Mothership) are read-only and cannot be edited by tenants.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException HTTP 403 if global template
     */
    public function edit(int $id)
    {
        $template = $this->repository->find($id);

        // Guard: templates com unique_id 'global-*' são do Mothership e imutáveis por tenants.
        if ($template->is_global ?? false) {
            abort(403, 'Templates globais são somente leitura e só podem ser alterados no painel Mothership.');
        }

        return view('lawfirm::Legal.modelos.edit', compact('template'));
    }

    /**
     * Update a local template.
     * Global templates (Mothership) are rejected with HTTP 403 — they are read-only.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException HTTP 403 if global template
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'titulo'   => 'required|string|max:255',
            'tipo'     => 'required|string|max:50',
            'conteudo' => 'required|string',
        ]);

        $template = $this->repository->find($id);

        // Guard: bloqueia qualquer tentativa de escrita em template do Mothership.
        if ($template->is_global ?? false) {
            abort(403, 'Templates globais são somente leitura e só podem ser alterados no painel Mothership.');
        }

        $template->update([
            'titulo'       => $request->titulo,
            'tipo'         => $request->tipo,
            'area_direito' => $request->area_direito,
            'conteudo'     => $request->conteudo,
            'descricao'    => $request->descricao,
            'ativo'        => $request->boolean('ativo', true),
        ]);

        session()->flash('success', 'Modelo de documento atualizado com sucesso.');

        return redirect()->route('admin.modelos.index');
    }

    /**
     * Remove a local template.
     * Global templates (Mothership) cannot be deleted by tenants — HTTP 403.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException HTTP 403 if global template
     */
    public function destroy(int $id)
    {
        try {
            $template = $this->repository->find($id);

            // Guard: impede deleção de templates do Mothership.
            if ($template->is_global ?? false) {
                return response()->json([
                    'message' => 'Templates globais são somente leitura e só podem ser removidos no painel Mothership.',
                ], 403);
            }

            $template->delete();

            return response()->json(['message' => 'Modelo de documento excluído com sucesso.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Não foi possível excluir o modelo.'], 500);
        }
    }

    // =========================================================================
    // ENDPOINTS FOR PROCESS CONTEXT
    // =========================================================================

    /**
     * Returns the HTML block for the Modelos tab within a Processo.
     */
    public function index(int $processoId)
    {
        $processo = Processo::findOrFail($processoId);
        $templates = $this->repository->forProcesso($processo);

        return view('lawfirm::Legal.processos.tabs.modelos-tab', compact('processo', 'templates'));
    }

    /**
     * Renders a specific template with data from the specified Processo.
     *
     * Accepts either a numeric integer ID (backward-compat, assumes local)
     * or a string unique_id like "local-5" / "global-3".
     *
     * @param  string  $templateId  e.g. "local-5", "global-3", or plain "5"
     */
    public function render(int $processoId, string $templateId)
    {
        $processo = Processo::findOrFail($processoId);
        $template = $this->repository->findByUniqueId($templateId);

        $content = $this->service->render($template, $processo);

        return response()->json([
            'success'   => true,
            'titulo'    => $template->titulo.' - '.$processo->titulo,
            'conteudo'  => $content,
            'is_global' => $template->is_global,
        ]);
    }

    // =========================================================================
    // LAYOUT TEMPLATES (Cabeçalho / Rodapé)
    // =========================================================================

    /**
     * Returns the current Cabeçalho and Rodapé layout templates as JSON.
     * Used by the modal JS to inject header/footer into the rendered document.
     *
     * GET modelos-documentos/layout
     */
    public function getLayoutTemplates()
    {
        $cabecalho = DocumentTemplate::where('tipo', 'cabecalho')
            ->where('is_layout', true)
            ->orderByDesc('updated_at')
            ->first();

        $rodape = DocumentTemplate::where('tipo', 'rodape')
            ->where('is_layout', true)
            ->orderByDesc('updated_at')
            ->first();

        // Ensure default layouts exist on first access
        if (! $cabecalho) {
            $cabecalho = $this->createDefaultLayout('cabecalho');
        }
        if (! $rodape) {
            $rodape = $this->createDefaultLayout('rodape');
        }

        return response()->json([
            'success'   => true,
            'cabecalho' => $cabecalho ? $this->service->render($cabecalho) : '',
            'rodape'    => $rodape ? $this->service->render($rodape) : '',
        ]);
    }

    /**
     * Create or update the Cabeçalho/Rodapé layout template.
     *
     * POST modelos-documentos/layout/{tipo}   (tipo = cabecalho | rodape)
     */
    public function saveLayout(Request $request, string $tipo)
    {
        if (! in_array($tipo, ['cabecalho', 'rodape'], true)) {
            return response()->json(['message' => 'Tipo de layout inválido.'], 422);
        }

        $request->validate(['conteudo' => 'required|string']);

        $template = DocumentTemplate::where('tipo', $tipo)
            ->where('is_layout', true)
            ->first();

        $titulo = $tipo === 'cabecalho' ? 'Cabeçalho Padrão' : 'Rodapé Padrão';

        if ($template) {
            $template->update(['conteudo' => $request->conteudo, 'titulo' => $titulo]);
        } else {
            DocumentTemplate::create([
                'titulo'    => $titulo,
                'tipo'      => $tipo,
                'conteudo'  => $request->conteudo,
                'is_layout' => true,
                'ativo'     => true,
                'user_id'   => auth()->guard('user')->id(),
            ]);
        }

        return response()->json(['success' => true, 'message' => ucfirst($tipo).' salvo com sucesso.']);
    }

    /**
     * Save a rendered document (HTML) to S3 via SaasFileService and register it in ProcessDocuments.
     *
     * POST processos/{processoId}/modelos/salvar
     */
    public function saveGenerated(Request $request, int $processoId)
    {
        $request->validate([
            'titulo'        => 'required|string|max:255',
            'conteudo_html' => 'required|string',
        ]);

        $processo = Processo::findOrFail($processoId);
        $titulo = $request->input('titulo');
        $html = $request->input('conteudo_html');

        // Build a unique S3 path inside the process folder
        $slug = Str::slug($titulo);
        $filename = $slug.'-'.now()->format('YmdHis').'.html';
        $path = 'documentos-gerados/'.$processoId.'/'.$filename;

        // Persist to tenant S3 bucket via SaasFileService (Ironclad Rule §2.2)
        $stored = $this->fileService->storeRaw($path, $html);

        if (! $stored) {
            Log::error('[LF] saveGenerated: SaasFileService::storeRaw() falhou.', compact('path', 'processoId'));

            return response()->json(['success' => false, 'message' => 'Erro ao salvar o documento no armazenamento.'], 500);
        }

        // Register in GED process documents table
        ProcessDocument::create([
            'processo_id' => $processoId,
            'name'        => $titulo,
            'file_path'   => $path,
            'status'      => 'received',
            'notes'       => 'Documento gerado a partir de modelo.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Documento salvo com sucesso no Drive.',
            'path'    => $path,
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Creates the default layout template for 'cabecalho' or 'rodape'.
     */
    private function createDefaultLayout(string $tipo): DocumentTemplate
    {
        $userId = optional(auth()->guard('user')->user())->id ?? 1;

        $conteudo = match ($tipo) {
            'cabecalho' => '<table style="border-collapse: collapse; width: 100%; height: 22.3906px; border: none;"><colgroup> <col style="width: 15%;"> <col style="width: 85%;"> </colgroup>'
                .'<tbody>'
                .'<tr style="height: 22.3906px; border: none;">'
                .'<td style="height: 22.3906px; border: none;"><img src="https://advdf2g.suitezap.com.br/admin/build/assets/logo-C5fyIF8z.svg" alt="" width="130" height="30"></td>'
                .'<td style="text-align: center; font-weight: bold; height: 22.3906px; border: none;">{{escritorio_nome}}</td>'
                .'</tr>'
                .'</tbody>'
                .'</table>',
            'rodape' => '<p style="text-align:center;">'
                .'{{escritorio_logradouro}}, n&ordm; {{escritorio_numero}} - {{escritorio_complemento}} / CEP {{escritorio_cep}} - {{escritorio_uf}}<br>'
                .'{{escritorio_whatsapp}}</p>',
            default => '',
        };

        return DocumentTemplate::create([
            'titulo'    => $tipo === 'cabecalho' ? 'Cabeçalho Padrão' : 'Rodapé Padrão',
            'tipo'      => $tipo,
            'conteudo'  => $conteudo,
            'is_layout' => true,
            'ativo'     => true,
            'user_id'   => $userId,
        ]);
    }
}
