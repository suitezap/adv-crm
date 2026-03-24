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
    ];
}
