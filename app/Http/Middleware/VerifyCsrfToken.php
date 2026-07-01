<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'admin/mail/inbound-parse',
        'admin/web-forms/forms/*',
        'admin/lawfirm/financial/process/*/store',
        'api/webhooks/escavador',   // Callback público da API Escavador (V2 async)
        'api/webhooks/asaas',       // Callback público da API Asaas (SaaS)
        'api/webhooks/tenant-asaas', // Callback público da API Asaas (Tenant Finance)
        'api/webhooks/chatwoot',    // Callback público do Chatwoot (Atendimento)
        'admin/juridico/agenda/eventos/atualizar/*', // Drag-drop da Agenda (srcdoc iframe context)
        'admin/juridico/agenda/atividades',           // Create activity via modal (srcdoc iframe context)
    ];
}
