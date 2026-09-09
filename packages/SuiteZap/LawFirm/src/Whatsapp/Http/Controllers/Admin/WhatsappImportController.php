<?php

namespace SuiteZap\LawFirm\Whatsapp\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SuiteZap\LawFirm\GED\Services\DocumentService;
use SuiteZap\LawFirm\Legal\Models\Anexo;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\Legal\Models\ProcessoWhatsappMessage;
use SuiteZap\LawFirm\Legal\Models\WhatsappImport;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\SaaS\Services\SaasFileService;
use SuiteZap\LawFirm\Whatsapp\Jobs\ImportProcessoWhatsappMessages;
use SuiteZap\LawFirm\Whatsapp\Services\EvolutionService;

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
     * Export Whatsapp History (PDF) and Media as a ZIP archive.
     */
    public function exportZip(Request $request, $processoId, SaasFileService $fileService)
    {
        try {
            $processo = Processo::findOrFail($processoId);

            $query = $processo->whatsappMessages();

            if ($request->filled('import_id')) {
                $query->where('import_id', $request->import_id);
            }

            $messages = $query->orderBy('message_timestamp', 'asc')->get();

            if ($messages->isEmpty()) {
                return back()->with('error', 'Nenhuma mensagem encontrada para exportar.');
            }

            // 1. Generate PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('lawfirm::admin.processos.exports.whatsapp-pdf-export', [
                'processo' => $processo,
                'messages' => $messages,
            ]);
            $pdfContent = $pdf->output();

            // 2. Prepare ZIP
            $zipFileName = 'Exportacao_Whatsapp_Processo_'.$processoId.'_'.now()->format('YmdHis').'.zip';
            $tempDir = storage_path('app/temp_exports');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $zipPath = $tempDir . '/' . $zipFileName;

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return back()->with('error', 'Não foi possível criar o arquivo ZIP temporário.');
            }

            // Add PDF to ZIP
            $zip->addFromString('Historico_Mensagens.pdf', $pdfContent);

            // 3. Add Media Files
            $anexoIds = $messages->whereNotNull('anexo_id')->pluck('anexo_id')->unique();
            if ($anexoIds->isNotEmpty()) {
                $anexos = Anexo::whereIn('id', $anexoIds)->get();
                $zip->addEmptyDir('midias');
                foreach ($anexos as $anexo) {
                    if ($anexo->path && $fileService->exists($anexo->path)) {
                        $fileData = $fileService->get($anexo->path);
                        if ($fileData) {
                            $zip->addFromString('midias/' . $anexo->nome_original, $fileData);
                        }
                    }
                }
            }

            $zip->close();

            // 4. Return Download Response and delete file after send
            return response()->download($zipPath)->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Erro na exportação ZIP do WhatsApp: " . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Ocorreu um erro ao gerar a exportação: ' . $e->getMessage());
        }
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
     * Delete an import session, all its messages, their GED Anexo records,
     * and the physical files from storage (S3/MinIO/local).
     */
    public function deleteImport($processoId, $importId, SaasFileService $fileService)
    {
        try {
            $processo = Processo::findOrFail($processoId);

            $import = WhatsappImport::where('id', $importId)
                ->where('processo_id', $processoId)
                ->firstOrFail();

            // 1. Delete all messages for this import (iterando para disparar o evento 'deleting')
            // O evento 'deleting' no Model ProcessoWhatsappMessage apagará o Anexo.
            // O evento 'deleting' no Model Anexo apagará o arquivo físico no S3.
            $import->messages->each->delete();

            // 2. Delete the import record itself
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

    /**
     * Download media from WhatsApp on demand and store via SaasFileService.
     * Creates an Anexo record in GED so the file is visible in process documents
     * and accessed securely through the internal proxy route.
     */
    public function downloadMedia($messageId, EvolutionService $evolutionService, SaasFileService $fileService)
    {
        try {
            $msg = ProcessoWhatsappMessage::findOrFail($messageId);

            // Already downloaded — return existing proxy URL
            if ($msg->anexo_id) {
                $downloadUrl = route('admin.processos.download_attachment', $msg->anexo_id);
                return response()->json([
                    'success'      => true,
                    'download_url' => $downloadUrl,
                    'media_type'   => $msg->media_type,
                ]);
            }

            if (!$msg->payload) {
                throw new \Exception('Nenhum payload de mensagem disponível.');
            }

            $config = MotherShipService::getEvolutionConfig();
            if (!$config || empty($config['instance'])) {
                throw new \Exception('Instância do WhatsApp não configurada neste ambiente.');
            }

            // 1. Fetch Base64 from Evolution API
            $response = $evolutionService->getBase64FromMediaMessage($config['instance'], $msg->payload);

            if (!$response['success'] || empty($response['data']['base64'])) {
                throw new \Exception($response['error'] ?? 'Arquivo de mídia expirado ou não disponível no WhatsApp.');
            }

            $base64  = $response['data']['base64'];
            $mimeType = $response['data']['mimetype'] ?? 'application/octet-stream';

            // 2. Determine extension from mime
            $mimeMap = [
                'image/jpeg'   => 'jpg',
                'image/png'    => 'png',
                'image/gif'    => 'gif',
                'image/webp'   => 'webp',
                'audio/ogg; codecs=opus' => 'ogg',
                'audio/ogg'   => 'ogg',
                'audio/mp4'   => 'mp4',
                'audio/mpeg'  => 'mp3',
                'video/mp4'   => 'mp4',
                'application/pdf'  => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            ];
            $mimeBase  = trim(explode(';', $mimeType)[0]);
            $extension = $mimeMap[$mimeType] ?? $mimeMap[$mimeBase] ?? (explode('/', $mimeBase)[1] ?? 'bin');

            // 3. Decode base64 (handle optional data-URI prefix)
            if (strpos($base64, 'data:') === 0) {
                $base64 = explode(',', $base64)[1];
            }
            $fileData = base64_decode($base64);
            if (!$fileData) {
                throw new \Exception('Falha ao decodificar a mídia recebida.');
            }

            // 4. Build isolated path: {tenant_id}/processos/{id}/whatsapp_media/{uuid}.{ext}
            $processoId    = $msg->processo_id;
            $tenantId      = MotherShipService::getTenantId();
            $filename      = (string) Str::uuid() . '.' . $extension;
            $originalName  = 'WhatsApp_' . now()->format('Ymd_His') . '.' . $extension;
            $storagePath   = "{$tenantId}/processos/{$processoId}/whatsapp_media/{$filename}";

            // 5. Store via SaasFileService (respects filesystems.default: s3, minio, local)
            $stored = $fileService->storeRaw($storagePath, $fileData);
            if (!$stored) {
                throw new \Exception('Falha ao salvar o arquivo no armazenamento configurado.');
            }

            // 6. Determine media category for frontend
            $mediaType = 'document';
            if (str_starts_with($mimeBase, 'image/'))  $mediaType = 'image';
            elseif (str_starts_with($mimeBase, 'audio/')) $mediaType = 'audio';
            elseif (str_starts_with($mimeBase, 'video/')) $mediaType = 'video';

            // 7. Create GED Anexo record so file appears in process documents
            //    and uses the secure internal proxy (admin.processos.download_attachment)
            $processo = Processo::findOrFail($processoId);
            $anexo = $processo->anexos()->create([
                'path'          => $storagePath,
                'nome_original' => $originalName,
                'tipo_mime'     => $mimeBase,
                'tamanho'       => strlen($fileData),
                'caso_id'       => $processo->caso_id,
            ]);

            // 8. Update whatsapp message with GED reference
            $msg->update([
                'media_type'   => $mediaType,
                'anexo_id'     => $anexo->id,
                'media_source' => 'whatsapp_media',
            ]);

            $downloadUrl = route('admin.processos.download_attachment', $anexo->id);

            return response()->json([
                'success'      => true,
                'download_url' => $downloadUrl,
                'media_type'   => $mediaType,
                'anexo_id'     => $anexo->id,
                'filename'     => $originalName,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete individual downloaded media from S3 and GED.
     */
    public function deleteMedia($messageId, SaasFileService $fileService)
    {
        try {
            $msg = ProcessoWhatsappMessage::findOrFail($messageId);

            if ($msg->anexo_id) {
                $anexo = Anexo::find($msg->anexo_id);
                if ($anexo) {
                    // O evento 'deleting' no Model Anexo apagará o arquivo físico no S3 automaticamente.
                    $anexo->delete();
                }

                // Reset message media status
                $msg->update([
                    'media_url' => null,
                    'media_type' => null,
                    'media_source' => null,
                    'anexo_id' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Mídia excluída com sucesso.',
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Mensagem não encontrada.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error("Erro ao excluir mídia: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'error'   => 'Erro ao excluir mídia.',
            ], 500);
        }
    }
}
