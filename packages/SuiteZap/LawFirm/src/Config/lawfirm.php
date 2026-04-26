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
    'evolution' => [
        'api_url'       => env('EVOLUTION_API_URL'),       // dev fallback only
        'api_key'       => env('EVOLUTION_API_KEY'),       // dev fallback only
        'instance_name' => env('EVOLUTION_INSTANCE_NAME'), // dev fallback only
    ],
];

