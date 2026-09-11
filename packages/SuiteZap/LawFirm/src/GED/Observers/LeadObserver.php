<?php

namespace SuiteZap\LawFirm\GED\Observers;

use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Services\SaasFileService;
use Webkul\Lead\Models\Lead;

class LeadObserver
{
    /**
     * Handle the Lead "deleting" event.
     * Executado ANTES do registro ser removido do banco.
     */
    public function deleting(Lead $lead)
    {
        // Carrega os anexos antes que a Foreign Key Cascade os apague do banco
        $attachments = $lead->emails()->get(); // Krayin v2 pode vincular anexos a emails ou diretamente
        // No Krayin padrão, anexos diretos ficam em 'lead_attachments' ou via FileRepository.
        // Vamos focar na relação direta 'attachments' se existir, ou verificar a tabela padrão.

        // Verificação Genérica para Leads do Krayin (baseado na estrutura padrão)
        if (method_exists($lead, 'attachments')) {
            foreach ($lead->attachments as $attachment) {
                $this->deleteFile($attachment->path);
            }
        }

        Log::info("SAAS CLEANUP: Processo #{$lead->id} está sendo excluído. Tentativa de limpeza de arquivos concluída.");
    }

    private function deleteFile($path)
    {
        if (! $path) {
            return;
        }

        $fileService = app(SaasFileService::class);

        if ($fileService->exists($path)) {
            try {
                $fileService->delete($path);
                Log::info(" > Arquivo apagado: {$path}");
            } catch (\Exception $e) {
                Log::error(" > Erro ao apagar arquivo {$path}: ".$e->getMessage());
            }
        }
    }
}
