<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LawFirm / SuiteZap — Configurações Globais
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE: Configurações sensíveis como api_secret são lidas diretamente
    | do banco de dados Mothership (tabela `app_config`), eliminando a necessidade
    | de variáveis de ambiente por stack.
    |
    | O MothershipTemplateController lê o segredo via:
    |   DB::connection('mothership')->table('app_config')->where('key','api_secret')
    |
    | O .env MOTHERSHIP_API_SECRET é mantido apenas como fallback para
    | ambientes onde a migration app_config ainda não foi executada.
    */

    /*
    | Fallback de segredo para autenticação de API do Mothership.
    | Preferência: lido de `mothership.app_config.api_secret` (zero deploy).
    | Fallback: esta variável de ambiente (para migração gradual).
    */
    'mothership_secret' => env('MOTHERSHIP_API_SECRET', null),

    /*
    |--------------------------------------------------------------------------
    | Evolution API (WhatsApp)
    |--------------------------------------------------------------------------
    |
    | Fallback para ambientes sem configuração via MotherShip.
    | No fluxo normal, MotherShipService::getEvolutionConfig() tem prioridade.
    */
    'evolution' => [
        'api_url'       => env('EVOLUTION_API_URL'),
        'api_key'       => env('EVOLUTION_API_KEY'),
        'instance_name' => env('EVOLUTION_INSTANCE_NAME'),
    ],
];
