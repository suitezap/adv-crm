<?php

namespace SuiteZap\LawFirm\Whatsapp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * BlockSuspendedImport
 *
 * Isola as rotas de Importação de Histórico do WhatsApp (módulo suspenso,
 * AGENTS.md §8) na camada de rota — sem tocar nos arquivos suspensos.
 * Toda chamada recebe 410 Gone + log. Reativar só com aprovação explícita.
 */
class BlockSuspendedImport
{
    public function handle(Request $request, Closure $next): mixed
    {
        Log::warning('[WhatsappImport] Rota suspensa alcançada (§8).', [
            'path' => $request->path(),
            'user' => auth()->guard('user')->id(),
        ]);

        abort(410, 'Importação de histórico do WhatsApp suspensa.');
    }
}
