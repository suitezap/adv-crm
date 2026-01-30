<?php

namespace SuiteZap\LawFirm\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use SuiteZap\LawFirm\Models\ChecklistTemplate;
use SuiteZap\LawFirm\Models\ProcessDocument;

class ProcessDocumentController extends Controller
{
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
                'name' => $name,
            ], [
                'status' => 'pending' // pending, received
            ]);
        }

        // --- INICIO BLOCO WHATSAPP ---
        try {
            $processo = \SuiteZap\LawFirm\Models\Processo::with('person')->find($processId);

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

            if ($phone && !empty($templateMsg)) {

                // 4. Gerar Lista de Documentos
                $docListString = "";
                if ($template && !empty($template->items)) {
                    foreach ($template->items as $item) {
                        $name = is_array($item) ? ($item['name'] ?? $item) : $item;
                        $docListString .= "- " . $name . "\n";
                    }
                }

                // 5. Substituir Variáveis
                $msg = str_replace(
                    ['{cliente_nome}', '{processo_titulo}', '{kit_nome}', '{lista_documentos}'],
                    [
                        $processo->person->name,
                        $processo->titulo ?? 'Processo',
                        $template->name ?? 'Documentação',
                        $docListString
                    ],
                    $templateMsg
                );

                // 6. Enviar via Service
                $evolutionService = app(\SuiteZap\LawFirm\Services\Whatsapp\EvolutionService::class);
                $config = \SuiteZap\LawFirm\Services\MotherShipService::getEvolutionConfig();
                $instanceName = $config['instance'] ?? env('EVOLUTION_INSTANCE_NAME');
                $evolutionService->sendMessage($instanceName, $phone, $msg);

                \Illuminate\Support\Facades\Log::info("Solicitação de documentos enviada via WhatsApp para {$processo->person->name}");
                session()->flash('success', 'Checklist importado e solicitação enviada via WhatsApp!');
                return redirect()->back();
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao enviar solicitação de documentos: " . $e->getMessage());
            // Não interrompa o fluxo principal, apenas logue o erro.
        }
        // --- FIM BLOCO WHATSAPP ---
    }

    // Atualiza o status de um documento (Ex: Pendente -> Recebido)
    public function updateStatus(Request $request, $id)
    {
        $document = ProcessDocument::findOrFail($id);
        $document->update([
            'status' => $request->status,
            'notes' => $request->notes
        ]);

        session()->flash('success', 'Status do documento atualizado.');
        return redirect()->back();
    }

    // Deletar um item da lista
    public function destroy($id)
    {
        ProcessDocument::findOrFail($id)->delete();
        session()->flash('success', 'Documento removido da lista.');
        return redirect()->back();
    }

    // Enviar Checklist Selecionado via WhatsApp
    public function sendChecklist(Request $request, $processId)
    {
        $request->validate(['selected_documents' => 'required|array']);

        try {
            $processo = \SuiteZap\LawFirm\Models\Processo::with('person')->findOrFail($processId);

            // 1. Verificar Telefone
            $phone = null;
            if ($processo && $processo->person) {
                // Fix for "Undefined relationship" - contact_numbers is an array cast
                $contactNumbers = collect($processo->person->contact_numbers);
                $phoneData = $contactNumbers->first();
                $phone = is_object($phoneData) ? $phoneData->value : ($phoneData['value'] ?? null);
            }

            if (!$phone) {
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
            $docListString = "";
            foreach ($documents as $doc) {
                $statusIcon = $doc->status == 'received' ? '✅' : ($doc->status == 'approved' ? '☑️' : '⬜');
                $docListString .= "{$statusIcon} " . $doc->name . ($doc->notes ? " ({$doc->notes})" : "") . "\n";
            }

            // 5. Substituir Variáveis
            $msg = str_replace(
                ['{cliente_nome}', '{processo_titulo}', '{kit_nome}', '{lista_documentos}'],
                [
                    $processo->person->name,
                    $processo->titulo ?? 'Processo',
                    'Seleção Manual',
                    $docListString
                ],
                $templateMsg
            );

            // 6. Enviar via Service
            $evolutionService = app(\SuiteZap\LawFirm\Services\Whatsapp\EvolutionService::class);

            $config = \SuiteZap\LawFirm\Services\MotherShipService::getEvolutionConfig();
            $instanceName = $config['instance'] ?? env('EVOLUTION_INSTANCE_NAME');

            $evolutionService->sendMessage($instanceName, $phone, $msg);

            session()->flash('success', 'Solicitação de documentos enviada com sucesso!');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao enviar checklist manual: " . $e->getMessage());
            session()->flash('error', 'Erro ao enviar mensagem: ' . $e->getMessage());
        }

        return redirect()->back();
    }
}
