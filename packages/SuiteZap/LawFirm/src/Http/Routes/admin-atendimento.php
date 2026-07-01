<?php

use Illuminate\Support\Facades\Route;
use SuiteZap\LawFirm\Atendimento\Http\Controllers\ChatwootWebhookController;

// ============================================================================
// PUBLIC WEBHOOK — /api/webhooks/chatwoot (sem auth, sem CSRF)
//
// Recebe callbacks do Chatwoot para o tenant atual.
//
// Segurança:
//   - Isento de CSRF via VerifyCsrfToken::$except no app principal.
//   - Validação da assinatura HMAC-SHA1 (X-Chatwoot-Signature) via webhook_token
//     do tenant no Mothership (Zero-.env rule).
//   - Cross-tenant guard via inbox_id comparado à config do tenant.
//
// @see ChatwootWebhookController::handle()
// @see ARCHITECTURE_LawFirm_orient.md §15
// ============================================================================

Route::middleware(['api'])->group(function () {
    Route::post('api/webhooks/chatwoot', [ChatwootWebhookController::class, 'handle'])
        ->name('webhooks.chatwoot');
});
