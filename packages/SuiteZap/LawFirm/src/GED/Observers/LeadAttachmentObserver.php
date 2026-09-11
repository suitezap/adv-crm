<?php

namespace SuiteZap\LawFirm\GED\Observers;

use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\SaaS\Services\SaasFileService;
use Webkul\Lead\Models\LeadAttachment;

class LeadAttachmentObserver
{
    /**
     * Handle the LeadAttachment "deleting" event.
     */
    public function deleting(LeadAttachment $attachment)
    {
        if ($attachment->path) {
            $fileService = app(SaasFileService::class);

            if ($fileService->exists($attachment->path)) {
                try {
                    $fileService->delete($attachment->path);
                    Log::info("SAAS FILE CLEANUP: Anexo individual removido: {$attachment->path}");
                } catch (\Exception $e) {
                    Log::error("SAAS FILE ERROR: Falha ao apagar anexo {$attachment->path}: ".$e->getMessage());
                }
            }
        }
    }
}
