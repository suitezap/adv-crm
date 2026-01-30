<?php

namespace SuiteZap\LawFirm\Observers;

use Webkul\Lead\Models\LeadAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LeadAttachmentObserver
{
    /**
     * Handle the LeadAttachment "deleting" event.
     */
    public function deleting(LeadAttachment $attachment)
    {
        if ($attachment->path) {
            // O ServiceProvider já configurou o bucket correto do tenant no boot
            if (Storage::disk('s3')->exists($attachment->path)) {
                try {
                    Storage::disk('s3')->delete($attachment->path);
                    Log::info("SAAS FILE CLEANUP: Anexo individual removido: {$attachment->path}");
                } catch (\Exception $e) {
                    Log::error("SAAS FILE ERROR: Falha ao apagar anexo {$attachment->path}: " . $e->getMessage());
                }
            }
        }
    }
}
