<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LawFirm / SuiteZap — Configurações Globais
    |--------------------------------------------------------------------------
    |
    | REGRA ZERO: Configurações sensíveis (tokens, API keys, instâncias) são
    | lidas exclusivamente do banco MotherShip via MotherShipService.
    | Valores env() abaixo são FALLBACK para ambiente de desenvolvimento local
    | e NÃO devem ser usados em produção/staging.
    |
    */

    /*
    | Segredo para autenticação de API do Mothership.
    | Fonte primária: `mothership.app_config` key `api_secret` (getApiSecretFromDb()).
    | Fallback DEV: esta variável de ambiente.
    */
    'mothership_secret' => env('MOTHERSHIP_API_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Evolution API (WhatsApp) — Fallback Local
    |--------------------------------------------------------------------------
    |
    | ⛔ PRODUÇÃO: Use MotherShipService::getEvolutionConfig() — OBRIGATÓRIO.
    | ✅ DEV/LOCAL: Estes valores env() são usados como fallback pelo
    |               ConnectionController::getInstanceName() quando o MotherShip
    |               não está configurado (ex: php artisan serve local).
    |
    | Canonical source: MotherShipService::getEvolutionConfig()
    | Returns: ['base_url', 'instance', 'token'] | null
    */
    /*
    | A configuração 'evolution' e seus fallbacks com env() foram removidos.
    | Todo o ecossistema SaaS DEVE usar MotherShipService::getEvolutionConfig()
    | para garantir a conformidade multi-tenant em produção.
    */
];
