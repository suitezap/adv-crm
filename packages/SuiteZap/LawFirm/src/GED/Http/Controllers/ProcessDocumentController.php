<?php

namespace SuiteZap\LawFirm\GED\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\GED\Models\ProcessDocument;
use SuiteZap\LawFirm\GED\Services\DocumentService;
use SuiteZap\LawFirm\Legal\Models\ChecklistTemplate;
use SuiteZap\LawFirm\Legal\Models\LawPersonDetail;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SaasFileService;

class ProcessDocumentController extends Controller
{
    protected $documentService;

    protected $fileService;

    public function __construct(DocumentService $documentService, SaasFileService $fileService)
    {
        $this->documentService = $documentService;
        $this->fileService = $fileService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'processo_id' => 'required|exists:processos,id',
            'anexos.*'    => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:20480', // 20MB Max
        ], [
            'anexos.*.mimes' => 'Apenas arquivos PDF, Image (JPG/PNG) e Word (DOC/DOCX) são permitidos.',
            'anexos.*.max'   => 'O tamanho máximo do arquivo é 20MB.',
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
     * @param  int  $id
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

    /**
     * Remove the specified checklist item from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroyChecklistItem($id)
    {
        try {
            ProcessDocument::findOrFail($id)->delete();

            if (request()->ajax()) {
                return response()->json(['message' => 'Item do checklist excluído com sucesso.', 'status' => 'success']);
            }

            return redirect()->back()->with('success', 'Item do checklist excluído com sucesso.');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json(['message' => 'Erro ao excluir item do checklist.', 'status' => 'error'], 500);
            }

            return redirect()->back()->with('error', 'Erro ao excluir item do checklist.');
        }
    }

    /**
     * Download the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function download($id)
    {
        try {
            $document = \SuiteZap\LawFirm\GED\Models\ProcessDocument::findOrFail($id);

            if (empty($document->file_path)) {
                return redirect()->back()->with('error', 'Arquivo não encontrado (Caminho vazio).');
            }

            // Usa SaasFileService para garantir acesso ao bucket correto do Tenant
            $contents = $this->fileService->get($document->file_path);

            if ($contents === null) {
                return redirect()->back()->with('error', 'Arquivo não encontrado no servidor remoto.');
            }

            $mimeType = $this->fileService->mimeType($document->file_path) ?? 'application/octet-stream';
            $filename = $document->name ?? basename($document->file_path);

            return response($contents, 200, [
                'Content-Type'        => $mimeType,
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao baixar documento {$id}: ".$e->getMessage());

            return redirect()->back()->with('error', 'Erro ao baixar documento: '.$e->getMessage());
        }
    }

    public function downloadAttachment($id)
    {
        try {
            $anexo = \SuiteZap\LawFirm\Legal\Models\Anexo::findOrFail($id);

            $path = $anexo->path;
            if (empty($path)) {
                return redirect()->back()->with('error', 'Arquivo não encontrado (Caminho vazio).');
            }

            if (str_starts_with($path, 'public/')) {
                $path = substr($path, 7);
            }

            // Usa SaasFileService para garantir acesso ao bucket correto do Tenant
            if (! $this->fileService->exists($path)) {
                return redirect()->back()->with('error', 'Arquivo físico não encontrado no servidor remoto.');
            }

            $contents = $this->fileService->get($path);

            if ($contents === null) {
                return redirect()->back()->with('error', 'Não foi possível ler o arquivo no servidor remoto.');
            }

            $mimeType = $this->fileService->mimeType($path) ?? 'application/octet-stream';
            $filename = $anexo->nome_original ?? 'anexo.pdf';

            return response($contents, 200, [
                'Content-Type'        => $mimeType,
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao baixar anexo {$id}: ".$e->getMessage());

            return redirect()->back()->with('error', 'Erro ao baixar anexo: '.$e->getMessage());
        }
    }

    // --- FROM LEGACY CONTROLLER ---

    // Importa os itens de um Template para o Processo Atual
    public function importTemplate(Request $request, $processId)
    {
        $request->validate(['template_id' => 'required|exists:law_checklist_templates,id']);

        $template = ChecklistTemplate::find($request->template_id);

        // Itera sobre os itens do JSON e cria os registros
        foreach ($template->items as $item) {
            $name = is_array($item) ? ($item['name'] ?? 'Documento') : $item;

            ProcessDocument::firstOrCreate([
                'processo_id' => $processId,
                'name'        => $name,
            ], [
                'status' => 'pending', // pending, received
            ]);
        }

        // --- INICIO BLOCO WHATSAPP ---
        try {
            $processo = Processo::with('person')->find($processId);

            // 2. Verificar Telefone
            $phone = null;
            if ($processo && $processo->person) {
                // Fix for "Undefined relationship" - contact_numbers is an array cast
                $contactNumbers = collect($processo->person->contact_numbers);
                $phoneData = $contactNumbers->first();
                $phone = is_object($phoneData) ? $phoneData->value : ($phoneData['value'] ?? null);
            }

            // 3. Carregar Template
            $templateMsg = core()->getConfigData('lawfirm.whatsapp_templates.messages.document_request');

            if ($phone && ! empty($templateMsg)) {

                // 4. Gerar Lista de Documentos
                $docListString = '';
                if ($template && ! empty($template->items)) {
                    foreach ($template->items as $item) {
                        $name = is_array($item) ? ($item['name'] ?? $item) : $item;
                        $docListString .= '- '.$name."\n";
                    }
                }

                // 5. Substituir Variáveis
                $portalLink = route('lawfirm.public.portal.index', [
                    'id'    => $processo->id,
                    'token' => hash_hmac('sha256', $processo->id, config('app.key')),
                ]);

                $msg = str_replace(
                    ['{cliente_nome}', '{processo_titulo}', '{kit_nome}', '{lista_documentos}', '{link_portal}'],
                    [
                        $processo->person->name,
                        $processo->titulo ?? 'Processo',
                        $template->name ?? 'Documentação',
                        $docListString,
                        $portalLink,
                    ],
                    $templateMsg
                );

                // 6. Enviar via Service
                $evolutionService = app(\SuiteZap\LawFirm\Whatsapp\Services\EvolutionService::class);
                $config = MotherShipService::getEvolutionConfig();

                if (! $config || empty($config['instance'])) {
                    Log::error('ProcessDocumentController: Evolution API não configurada no MotherShip. WhatsApp não enviado.');
                } else {
                    $evolutionService->sendMessage($config['instance'], $phone, $msg);
                    Log::info("Solicitação de documentos enviada via WhatsApp para {$processo->person->name}");
                }

                session()->flash('success', 'Checklist importado'.($config ? ' e solicitação enviada via WhatsApp!' : '.'));

                return redirect()->back();
            }

        } catch (\Exception $e) {
            Log::error('Erro ao enviar solicitação de documentos: '.$e->getMessage());
            // Não interrompa o fluxo principal, apenas logue o erro.
        }
        // --- FIM BLOCO WHATSAPP ---

        // ✅ FIX: Always return a redirect (missing return when WhatsApp is not configured)
        session()->flash('success', 'Checklist importado com sucesso.');

        return redirect()->back();
    }

    /**
     * Add a single item to the checklist manually.
     *
     * @param  int  $processId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function addItem(Request $request, $processId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $doc = ProcessDocument::create([
            'processo_id' => $processId,
            'name'        => trim($request->input('name')),
            'notes'       => trim($request->input('notes', '')),
            'status'      => 'pending',
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Item adicionado ao checklist.',
                'doc'     => $doc,
            ]);
        }

        session()->flash('success', 'Item adicionado ao checklist.');

        return redirect()->back();
    }

    // Atualiza o status de um documento (Ex: Pendente -> Recebido)
    public function updateStatus(Request $request, $id)
    {
        $document = ProcessDocument::findOrFail($id);
        $document->update([
            'status' => $request->status,
            'notes'  => $request->notes,
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Status atualizado com sucesso.',
            ]);
        }

        session()->flash('success', 'Status do documento atualizado.');

        return redirect()->back();
    }

    // Enviar Checklist Selecionado via WhatsApp
    public function sendChecklist(Request $request, $processId)
    {
        $request->validate(['selected_documents' => 'required|array']);

        try {
            $processo = Processo::with('person')->findOrFail($processId);

            // 1. Verificar Telefone
            $phone = null;
            if ($processo && $processo->person) {
                // Fix for "Undefined relationship" - contact_numbers is an array cast
                $contactNumbers = collect($processo->person->contact_numbers);
                $phoneData = $contactNumbers->first();
                $phone = is_object($phoneData) ? $phoneData->value : ($phoneData['value'] ?? null);
            }

            if (! $phone) {
                session()->flash('error', 'O cliente não possui telefone cadastrado.');

                return redirect()->back();
            }

            // 2. Carregar Template
            $templateMsg = core()->getConfigData('lawfirm.whatsapp_templates.messages.document_request');

            if (empty($templateMsg)) {
                // Fallback Template
                $templateMsg = "Olá {cliente_nome},\n\nReferente ao processo {processo_titulo}, precisamos dos seguintes documentos:\n\n{lista_documentos}\n\nPor favor, nos envie assim que possível.";
            }

            // 3. Filtrar Documentos
            $documents = ProcessDocument::whereIn('id', $request->selected_documents)
                ->where('processo_id', $processId) // Security check
                ->get();

            if ($documents->isEmpty()) {
                session()->flash('error', 'Nenhum documento válido selecionado.');

                return redirect()->back();
            }

            // 4. Gerar Lista
            $docListString = '';
            foreach ($documents as $doc) {
                $statusIcon = $doc->status == 'received' ? '✅' : ($doc->status == 'approved' ? '☑️' : '⬜');
                $docListString .= "{$statusIcon} ".$doc->name.($doc->notes ? " ({$doc->notes})" : '')."\n";
            }

            // 5. Substituir Variáveis
            $portalLink = route('lawfirm.public.portal.index', [
                'id'    => $processo->id,
                'token' => hash_hmac('sha256', $processo->id, config('app.key')),
            ]);

            $msg = str_replace(
                ['{cliente_nome}', '{processo_titulo}', '{kit_nome}', '{lista_documentos}', '{link_portal}'],
                [
                    $processo->person->name,
                    $processo->titulo ?? 'Processo',
                    'Seleção Manual',
                    $docListString,
                    $portalLink,
                ],
                $templateMsg
            );

            // 6. Enviar via Service
            $evolutionService = app(\SuiteZap\LawFirm\Whatsapp\Services\EvolutionService::class);

            $config = MotherShipService::getEvolutionConfig();

            if (! $config || empty($config['instance'])) {
                Log::error('ProcessDocumentController: Evolution API não configurada no MotherShip. Checklist WhatsApp não enviado.');
                session()->flash('warning', 'Checklist enviado, mas WhatsApp não está configurado para este workspace.');
            } else {
                $evolutionService->sendMessage($config['instance'], $phone, $msg);
                session()->flash('success', 'Solicitação de documentos enviada com sucesso!');
            }

        } catch (\Exception $e) {
            Log::error('Erro ao enviar checklist manual: '.$e->getMessage());
            session()->flash('error', 'Erro ao enviar mensagem: '.$e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Gera e baixa o PDF da Procuração.
     */
    public function downloadProcuration($processId)
    {
        Carbon::setLocale('pt_BR');

        // Carrega Processo + Pessoa
        $process = Processo::with(['person'])->findOrFail($processId);
        $person = $process->person;

        // Busca os detalhes da extensão LawFirm
        $detail = $person ? LawPersonDetail::where('person_id', $person->id)->first() : null;

        // --- 1. MONTAGEM DOS DADOS DO OUTORGANTE ---
        $client = [];

        if ($person) {
            $client['name'] = mb_strtoupper($person->name, 'UTF-8');

            // Documentos (Prioridade: Extension > Person > Custom Attribute)
            $client['cpf'] = $detail->cpf ?? $person->cpf ?? $person->custom_attributes['cpf'] ?? null;
            $client['rg'] = $detail->rg ?? $person->rg ?? null;

            // Nacionalidade, Estado Civil e Profissão (Se houver na extensão)
            $client['nationality'] = $detail->nacionalidade ?? null;
            $client['civil_status'] = $detail->estado_civil ?? null;
            $client['profession'] = $detail->profissao ?? null;

            // --- LÓGICA DE ENDEREÇO (A Correção Principal) ---
            // Verifica se os campos detalhados da extensão LawFirm estão preenchidos
            if ($detail && ($detail->logradouro || $detail->cep)) {
                // Monta endereço a partir dos campos detalhados
                $parts = [];
                if ($detail->logradouro) {
                    $parts[] = $detail->logradouro;
                }
                if ($detail->numero) {
                    $parts[] = 'nº '.$detail->numero;
                }
                if ($detail->complemento) {
                    $parts[] = $detail->complemento;
                }
                if ($detail->bairro) {
                    $parts[] = $detail->bairro;
                }

                $cityState = [];
                if ($detail->cidade) {
                    $cityState[] = $detail->cidade;
                }
                if ($detail->uf) {
                    $cityState[] = $detail->uf;
                }

                if (! empty($cityState)) {
                    $parts[] = implode('/', $cityState);
                }
                if ($detail->cep) {
                    $parts[] = 'CEP '.$detail->cep;
                }

                $client['address'] = implode(', ', $parts);
            } else {
                $client['address'] = null;
            }
        } else {
            // Fallback caso não haja pessoa
            $client['name'] = 'OUTORGANTE NÃO DEFINIDO';
            $client['cpf'] = null;
            $client['rg'] = null;
            $client['nationality'] = null;
            $client['civil_status'] = null;
            $client['profession'] = null;
            $client['address'] = null;
        }

        // --- 2. DADOS DO ESCRITÓRIO & CIDADE ---
        $firmName = core()->getConfigData('lawfirm.settings.general.company_name');
        $firmOAB = core()->getConfigData('lawfirm.settings.general.oab_number');
        $firmAddress = core()->getConfigData('lawfirm.settings.general.address');

        // Dados do Advogado Específico do Processo
        $lawyerSpecificName = $process->advogado_responsavel_nome;
        $lawyerSpecificOAB = $process->advogado_responsavel_oab;

        // --- LÓGICA DE CIDADE (Prioridade: Campo Específico > Parsing) ---
        $cityConfig = core()->getConfigData('lawfirm.settings.general.city');

        if (! empty($cityConfig)) {
            $city = trim($cityConfig);
        } else {
            $city = 'Local';

            if ($firmAddress) {
                if (preg_match('/([\w\s]+)\s*\/\s*([A-Z]{2})/', $firmAddress, $matches)) {
                    $city = trim($matches[1]);
                } else {
                    $parts = preg_split('/[-–]/', $firmAddress);

                    foreach ($parts as $index => $part) {
                        $part = trim($part);
                        if (preg_match('/^[A-Z]{2}$/', $part)) {
                            if (isset($parts[$index - 1])) {
                                $candidate = trim($parts[$index - 1]);
                                if (! preg_match('/^(Jd\.|Jardim|Vila|Rua|Av\.|Alameda)/i', $candidate)) {
                                    $city = $candidate;
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }

        $city = trim($city, ' .,;-');

        $dateExtenso = Carbon::now()->translatedFormat('d \d\e F \d\e Y');

        $pdf = Pdf::loadView('lawfirm::documents.pdf.procuration', compact(
            'process',
            'client',
            'firmName',
            'firmOAB',
            'firmAddress',
            'lawyerSpecificName',
            'lawyerSpecificOAB',
            'city',
            'dateExtenso'
        ));

        return $pdf->download('procuracao.pdf');
    }

    /**
     * Gera e baixa o PDF do Contrato de Honorários.
     */
    public function downloadContract($processId)
    {
        Carbon::setLocale('pt_BR');

        // Carrega Processo + Pessoa
        $process = Processo::with(['person'])->findOrFail($processId);
        $person = $process->person;

        $detail = $person ? LawPersonDetail::where('person_id', $person->id)->first() : null;

        $client = [];

        if ($person) {
            $client['name'] = mb_strtoupper($person->name, 'UTF-8');
            $client['cpf'] = $detail->cpf ?? $person->cpf ?? $person->custom_attributes['cpf'] ?? null;
            $client['rg'] = $detail->rg ?? $person->rg ?? null;
            $client['doc_type'] = 'CPF';
            $client['doc'] = $client['cpf'] ?? '________________';
            $client['nationality'] = $detail->nacionalidade ?? null;
            $client['civil_status'] = $detail->estado_civil ?? null;
            $client['profession'] = $detail->profissao ?? null;

            if ($detail && ($detail->logradouro || $detail->cep)) {
                $parts = [];
                if ($detail->logradouro) {
                    $parts[] = $detail->logradouro;
                }
                if ($detail->numero) {
                    $parts[] = 'nº '.$detail->numero;
                }
                if ($detail->complemento) {
                    $parts[] = $detail->complemento;
                }
                if ($detail->bairro) {
                    $parts[] = $detail->bairro;
                }

                $cityState = [];
                if ($detail->cidade) {
                    $cityState[] = $detail->cidade;
                }
                if ($detail->uf) {
                    $cityState[] = $detail->uf;
                }

                if (! empty($cityState)) {
                    $parts[] = implode('/', $cityState);
                }
                if ($detail->cep) {
                    $parts[] = 'CEP '.$detail->cep;
                }

                $client['address'] = implode(', ', $parts);
            } else {
                $client['address'] = null;
            }
        } else {
            $client['name'] = 'OUTORGANTE NÃO DEFINIDO';
            $client['cpf'] = null;
            $client['rg'] = null;
            $client['nationality'] = null;
            $client['civil_status'] = null;
            $client['profession'] = null;
            $client['address'] = null;
        }

        $firmName = core()->getConfigData('lawfirm.settings.general.company_name');
        $firmOAB = core()->getConfigData('lawfirm.settings.general.oab_number');
        $firmAddress = core()->getConfigData('lawfirm.settings.general.address');

        $lawyerSpecificName = $process->advogado_responsavel_nome;
        $lawyerSpecificOAB = $process->advogado_responsavel_oab;

        $cityConfig = core()->getConfigData('lawfirm.settings.general.city');

        if (! empty($cityConfig)) {
            $city = trim($cityConfig);
        } else {
            $city = 'Local';

            if ($firmAddress) {
                if (preg_match('/([\w\s]+)\s*\/\s*([A-Z]{2})/', $firmAddress, $matches)) {
                    $city = trim($matches[1]);
                } else {
                    $parts = preg_split('/[-–]/', $firmAddress);

                    foreach ($parts as $index => $part) {
                        $part = trim($part);
                        if (preg_match('/^[A-Z]{2}$/', $part)) {
                            if (isset($parts[$index - 1])) {
                                $candidate = trim($parts[$index - 1]);
                                if (! preg_match('/^(Jd\.|Jardim|Vila|Rua|Av\.|Alameda)/i', $candidate)) {
                                    $city = $candidate;
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }

        $city = trim($city, ' .,;-');

        $dateExtenso = Carbon::now()->translatedFormat('d \d\e F \d\e Y');

        $pdf = Pdf::loadView('lawfirm::documents.pdf.contract', compact(
            'process',
            'client',
            'firmName',
            'firmOAB',
            'firmAddress',
            'lawyerSpecificName',
            'lawyerSpecificOAB',
            'city',
            'dateExtenso'
        ));

        return $pdf->download('contrato_honorarios.pdf');
    }
}
