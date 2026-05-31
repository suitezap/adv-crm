<?php

namespace SuiteZap\LawFirm\SaaS\Services;

use SuiteZap\LawFirm\SaaS\Models\Subscription;
use SuiteZap\LawFirm\SaaS\Models\InfrastructureNode;
use SuiteZap\LawFirm\SaaS\Models\Tenant;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;
use Webkul\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MotherShipService
{
    /**
     * Retorna o ID do Tenant atual.
     */
    public static function getTenantId()
    {
        return config('lawfirm.tenant_id', env('TENANT_ID'));
    }

    /**
     * Retorna a assinatura do Tenant atual.
     */
    public static function getCurrentSubscription()
    {
        $tenantId = self::getTenantId();

        if (!$tenantId) {
            Log::warning('SAAS: TENANT_ID não configurado no .env');
            return null;
        }

        return Cache::remember("tenant_{$tenantId}_subscription", 60, function () use ($tenantId) {
            $sub = Subscription::on('mothership')
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$sub) {
                Log::error("SAAS: Nenhuma assinatura encontrada para tenant: {$tenantId}");
            }

            return $sub;
        });
    }

    /**
     * Verifica se pode ativar/criar novos usuários ativos.
     * Limit 0 ou null = sem limite (ilimitado).
     */
    public static function canCreateUser(): bool
    {
        $subscription = self::getCurrentSubscription();

        if (!$subscription) {
            Log::warning('SAAS: Bloqueio de criação de usuário — assinatura não localizada.');
            return false;
        }

        $limit = (int) $subscription->max_users;

        // 0 ou null = sem limite de usuários ativos
        if ($limit <= 0) {
            return true;
        }

        $currentCount = \Webkul\User\Models\User::where('status', 1)->count();

        return $currentCount < $limit;
    }

    /**
     * Retorna as configurações do Tenant (incluindo bucket e chaves de API).
     */
    public static function getTenantConfig()
    {
        $tenantId = config('lawfirm.tenant_id', env('TENANT_ID'));

        if (!$tenantId) {
            return null;
        }

        // Cache de longa duração (1 hora) pois configurações de infra mudam pouco
        return Cache::remember("tenant_{$tenantId}_config", 3600, function () use ($tenantId) {
            return \SuiteZap\LawFirm\SaaS\Models\Tenant::on('mothership')
                ->where('id', $tenantId)
                ->first();
        });
    }

    /**
     * Recupera um valor da tabela app_config do Mothership (com cache de 5 minutos).
     */
    public static function getAppConfig(string $key)
    {
        return Cache::remember("mothership_app_config_{$key}", 300, function () use ($key) {
            try {
                return \Illuminate\Support\Facades\DB::connection('mothership')
                    ->table('app_config')
                    ->where('key', $key)
                    ->value('value');
            } catch (\Exception $e) {
                Log::warning("[MotherShipService] Falha ao ler {$key} de app_config: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Consome o endpoint api/exchange_rate.php do MotherShip para obter a taxa
     * de câmbio soberana (consumer_rate) e o multiplicador de SuiteCoins.
     *
     * Retorna defaults seguros se o MotherShip estiver offline (503-safe).
     *
     * @return array{consumer_rate: float, suitecoin_multiplier: int, billing_consensus: array}
     */
    public static function getExchangeRate(): array
    {
        $defaults = [
            'consumer_rate'       => 5.75,
            'suitecoin_multiplier' => 10,
            'billing_consensus'   => [],
        ];

        return Cache::remember('mothership_exchange_rate', 300, function () use ($defaults) {
            try {
                $apiSecret  = self::getAppConfig('api_secret') ?? env('MOTHERSHIP_API_SECRET');
                $baseUrl    = rtrim(env('MOTHERSHIP_BASE_URL', ''), '/');

                if (empty($baseUrl) || empty($apiSecret)) {
                    Log::warning('[MotherShipService] getExchangeRate: MOTHERSHIP_BASE_URL ou api_secret não configurados.');
                    return $defaults;
                }

                $response = \Illuminate\Support\Facades\Http::timeout(5)
                    ->withHeaders(['X-Api-Secret' => $apiSecret])
                    ->get("{$baseUrl}/api/exchange_rate.php");

                if (!$response->successful()) {
                    Log::warning('[MotherShipService] getExchangeRate: HTTP ' . $response->status());
                    return $defaults;
                }

                $data = $response->json();

                return [
                    'consumer_rate'       => (float) ($data['billing_consensus']['consumer_rate'] ?? $defaults['consumer_rate']),
                    'suitecoin_multiplier' => (int) ($data['suitecoin_multiplier'] ?? $defaults['suitecoin_multiplier']),
                    'billing_consensus'   => (array) ($data['billing_consensus'] ?? []),
                ];
            } catch (\Throwable $e) {
                Log::error('[MotherShipService] getExchangeRate falhou: ' . $e->getMessage());
                return $defaults;
            }
        });
    }

    /**
     * Retorna a configuração da Evolution API para o Tenant atual.
     * Prioriza o banco de dados MotherShip. Retorna null se não configurado.
     */
    public static function getEvolutionConfig()
    {
        // 1. Pega configs do Tenant (incluindo chaves e IDs de nós)
        $tenantConfig = self::getTenantConfig();

        if (!$tenantConfig || !$tenantConfig->evolution_node_id) {
            return null;
        }

        // 2. Busca o Nó de Infraestrutura (Servidor onde o WhatsApp está rodando)
        // Usamos cache curto aqui para não sobrecarregar o banco de infra
        $node = \Illuminate\Support\Facades\Cache::remember("infra_node_{$tenantConfig->evolution_node_id}", 300, function () use ($tenantConfig) {
            return \SuiteZap\LawFirm\SaaS\Models\InfrastructureNode::on('mothership')
                ->find($tenantConfig->evolution_node_id);
        });

        if (!$node) {
            return null;
        }

        return [
            'base_url' => rtrim($node->base_url, '/'),
            'instance' => $tenantConfig->evolution_instance_name,
            'token' => $tenantConfig->evolution_api_key ?: $node->api_key
        ];
    }

    /**
     * Retorna a configuração do N8N para o Tenant atual.
     * Busca via MotherShip (não usa env()).
     */
    public static function getN8nConfig()
    {
        $tenantConfig = self::getTenantConfig();

        if (!$tenantConfig || !$tenantConfig->n8n_node_id) {
            return null;
        }

        // Cache de 60s conforme especificado
        return Cache::remember("n8n_node_{$tenantConfig->n8n_node_id}", 60, function () use ($tenantConfig) {
            $node = InfrastructureNode::on('mothership')
                ->where('id', $tenantConfig->n8n_node_id)
                ->where('type', 'n8n')
                ->first();

            if (!$node) {
                return null;
            }

            return [
                'url' => rtrim($node->base_url, '/'),
                'api_key' => $node->api_key,
            ];
        });
    }

    /**
     * Retorna a configuração do DataJud para o Tenant atual.
     * Busca via MotherShip (não usa env()).
     */
    public static function getDataJudConfig()
    {
        // Tenta buscar de node se aplicável ou direto do app_config global, fornecendo fallback de chave Master do CNJ
        $apiKey = self::getAppConfig('datajud_api_key') ?? 'cDZHYzlZa0JadVREZDJCendQbXY6SkJlTzNjLV9TRENyQk1RdnFKZGRQdw==';

        return [
            'api_key' => $apiKey,
            'url' => 'https://api-publica.datajud.cnj.jus.br/api_publica'
        ];
    }

    /**
     * Configura dinamicamente o disco de armazenamento (S3/MinIO) do Tenant.
     * Deve ser chamado no boot do ServiceProvider.
     */
    public static function configureTenantStorage()
    {
        $tenantConfig = self::getTenantConfig();

        // Se não tiver tenant ou não tiver nó de storage definido, mantém o padrão do .env
        if (!$tenantConfig || !$tenantConfig->storage_node_id) {
            return;
        }

        // Cache curto para evitar queries em toda requisição, mas permitindo mudanças rápidas
        $storageNode = Cache::remember("infra_storage_node_{$tenantConfig->storage_node_id}", 60, function () use ($tenantConfig) {
            return InfrastructureNode::on('mothership')
                ->where('id', $tenantConfig->storage_node_id)
                ->first();
        });

        if (!$storageNode) {
            Log::warning("SAAS WARNING: Tenant {$tenantConfig->id} aponta para nó de storage {$tenantConfig->storage_node_id} inexistente.");
            return;
        }

        // Decodifica os metadados (esperado JSON com secret e region)
        // O Model InfrastructureNode já deve ter o cast de meta_data, mas garantimos aqui
        $metaData = is_array($storageNode->meta_data) ? $storageNode->meta_data : json_decode($storageNode->meta_data, true);

        // Se falhar o decode ou não tiver secret, aborta
        if (!$metaData || !isset($metaData['secret'])) {
            Log::error("SAAS ERROR: Nó de Storage {$storageNode->id} com metadados inválidos ou sem secret.");
            return;
        }

        // Configura o disco 's3' em tempo de execução
        config([
            'filesystems.disks.s3.driver' => 's3',
            'filesystems.disks.s3.key' => $storageNode->api_key,
            'filesystems.disks.s3.secret' => $metaData['secret'],
            'filesystems.disks.s3.region' => $metaData['region'] ?? 'us-east-1',
            'filesystems.disks.s3.bucket' => preg_replace('/[^a-z0-9.-]/', '-', strtolower($tenantConfig->minio_bucket_name ?? ($metaData['bucket'] ?? 'lawfirm-fallback'))),
            'filesystems.disks.s3.endpoint' => rtrim($storageNode->base_url, '/'),
            'filesystems.disks.s3.use_path_style_endpoint' => $metaData['use_path_style_endpoint'] ?? true,
            'filesystems.disks.s3.throw' => false,
        ]);

        Log::info("SAAS: Storage configurado dinamicamente para nó {$storageNode->id}");
    }

    /**
     * Retorna os Assistentes de IA disponíveis para o Tenant atual,
     * baseado nos módulos ativos da assinatura.
     *
     * @param  string  $tenantId       ID do tenant atual
     * @param  array   $activeModules  Módulos ativos da assinatura (ex: ['LEGAL', 'AI'])
     * @return \Illuminate\Support\Collection
     */
    public static function getAvailableAssistants(string $tenantId, array $activeModules)
    {
        $cacheKey = "tenant_{$tenantId}_available_assistants";

        return Cache::remember($cacheKey, 300, function () use ($tenantId, $activeModules) {
            return AssistantTemplate::on('mothership')
                ->select(['id', 'title', 'description', 'icon', 'required_module', 'category'])
                ->where('is_active', true)
                ->where(function ($query) use ($tenantId) {
                    $query->whereNull('tenant_id')           // Templates Globais
                        ->orWhere('tenant_id', $tenantId); // Templates do Cliente
                })
                ->where(function ($query) use ($activeModules) {
                    $query->whereNull('required_module')                // Templates livres
                        ->orWhereIn('required_module', $activeModules); // Módulo contratado
                })
                ->orderBy('category')
                ->orderBy('title')
                ->get();
        });
    }

    /**
     * Retorna o multiplicador de Markup e Janela de Cache para o Escavador.
     */
    public static function getEscavadorMarkup(): float
    {
        return Cache::remember('escavador_markup', 3600, function () {
            $val = \Illuminate\Support\Facades\DB::connection('mothership')->table('app_config')
                ->where('key', 'escavador_markup_percent')->value('value');
            return $val !== null ? (float) $val : 100.0;
        });
    }

    public static function getEscavadorCacheWindowHours(): int
    {
        return Cache::remember('escavador_cache_window_hours', 3600, function () {
            $val = \Illuminate\Support\Facades\DB::connection('mothership')->table('app_config')
                ->where('key', 'escavador_cache_window_hours')->value('value');
            return $val !== null ? (int) $val : 24;
        });
    }

    /**
     * Retorna a tabela de preços dinâmica dos serviços do Escavador diretamente
     * do banco de dados Mothership (tabela app_config).
     *
     * Permite que o painel administrativo global mude os preços sem mexer
     * no código da aplicação. Faz cache de 60 minutos.
     */
    public static function getEscavadorPrices(): array
    {
        return Cache::remember('escavador_prices', 3600, function () {
            $configs = \Illuminate\Support\Facades\DB::connection('mothership')->table('app_config')
                ->where('key', 'like', 'escavador_price_%')
                ->orWhere('key', 'like', 'datajud_price_%')
                ->pluck('value', 'key');

            return [
                'DATAJUD_CONSULTA_PUBLICA' => (float) ($configs['datajud_price_consulta_publica'] ?? 0.00),
                'CAPA_PROCESSO' => (float) ($configs['escavador_price_capa_processo'] ?? 0.01),
                'PDF_DIARIO' => (float) ($configs['escavador_price_pdf_diario'] ?? 0.02),
                'BUSCA_TERMO' => (float) ($configs['escavador_price_busca_termo'] ?? 0.03),
                'RESUMO_IA' => (float) ($configs['escavador_price_resumo_ia'] ?? 0.01),
                'API_V1_BUSCARPORTERMO' => (float) ($configs['escavador_price_api_v1_buscarportermo'] ?? 0.03),
                'API_V1_DOWNLOADDOPDFDAPGINADODIRIOOFICIAL' => (float) ($configs['escavador_price_api_v1_downloaddopdfdapginadodiriooficial'] ?? 0.02),
                'API_V1_OBTERPESSOA' => (float) ($configs['escavador_price_api_v1_obterpessoa'] ?? 0.02),
                'API_V1_PROCESSOSDEUMAPESSOA' => (float) ($configs['escavador_price_api_v1_processosdeumapessoa'] ?? 0.10),
                'INFO_INSTITUICAO' => (float) ($configs['escavador_price_info_instituicao'] ?? 0.02),
                'PROCESSOS_INSTITUICAO' => (float) ($configs['escavador_price_processos_instituicao'] ?? 0.03),
                'PESSOAS_INSTITUICAO' => (float) ($configs['escavador_price_pessoas_instituicao'] ?? 0.02),
                'API_V1_MOVIMENTAESDEUMPROCESSODO' => (float) ($configs['escavador_price_api_v1_movimentaesdeumprocessodo'] ?? 0.03),
                'API_V1_BUSCARPROCESSOSDOSDIRIOSPOROAB' => (float) ($configs['escavador_price_api_v1_buscarprocessosdosdiriosporoab'] ?? 0.03),
                'BUSCA_PROC_DIARIO_NUM' => (float) ($configs['escavador_price_busca_proc_diario_num'] ?? 0.03),
                'API_V1_ENVOLVIDOSDEUMPROCESSO' => (float) ($configs['escavador_price_api_v1_envolvidosdeumprocesso'] ?? 0.03),
                'API_V1_PESQUISARPROCESSONOTRIBUNAL' => (float) ($configs['escavador_price_api_v1_pesquisarprocessonotribunal'] ?? 0.18),
                'API_V1_PESQUISARPROCESSOSPORNOME' => (float) ($configs['escavador_price_api_v1_pesquisarprocessospornome'] ?? 0.10),
                'API_V1_PESQUISARPROCESSOSPORCPFOUCNPJ' => (float) ($configs['escavador_price_api_v1_pesquisarprocessosporcpfoucnpj'] ?? 0.10),
                'API_V1_PESQUISARPROCESSOSPOROAB' => (float) ($configs['escavador_price_api_v1_pesquisarprocessosporoab'] ?? 0.10),
                'API_V1_PESQUISARPROCESSOADMINISTRATIVONUP' => (float) ($configs['escavador_price_api_v1_pesquisarprocessoadministrativonup'] ?? 0.01),
                'API_V1_RETORNARUMAMOVIMENTAO' => (float) ($configs['escavador_price_api_v1_retornarumamovimentao'] ?? 0.01),
                'TRIBUNAIS_SISTEMAS' => (float) ($configs['escavador_price_tribunais_sistemas'] ?? 0.01),
                'TRIBUNAIS_DETALHES' => (float) ($configs['escavador_price_tribunais_detalhes'] ?? 0.01),
                'ORGAOS_ADMINISTRATIVOS' => (float) ($configs['escavador_price_orgaos_administrativos'] ?? 0.01),
                'API_V1_CONSULTAR_SALDO' => (float) ($configs['escavador_price_api_v1_consultar_saldo'] ?? 0.01),
                'API_V1_TODOS_ASYNC_RESULTADOS' => (float) ($configs['escavador_price_api_v1_todos_async_resultados'] ?? 0.01),
                'API_V1_RESULTADO_ASYNC_ID' => (float) ($configs['escavador_price_api_v1_resultado_async_id'] ?? 0.01),
                'API_V1_MARCAR_CALLBACKS' => (float) ($configs['escavador_price_api_v1_marcar_callbacks'] ?? 0.01),
                'API_V1_RETORNAR_CALLBACKS' => (float) ($configs['escavador_price_api_v1_retornar_callbacks'] ?? 0.01),
                'API_V1_REENVIAR_CALLBACK' => (float) ($configs['escavador_price_api_v1_reenviar_callback'] ?? 0.01),
                'API_V1_RETORNAR_ORIGENS' => (float) ($configs['escavador_price_api_v1_retornar_origens'] ?? 0.01),
                'API_V1_PAGINA_DIARIO' => (float) ($configs['escavador_price_api_v1_pagina_diario'] ?? 0.02),
                'API_V1_RETORNAR_MONITORAMENTOS' => (float) ($configs['escavador_price_api_v1_retornar_monitoramentos'] ?? 0.01),
                'API_V1_RETORNAR_MONITORAMENTO' => (float) ($configs['escavador_price_api_v1_retornar_monitoramento'] ?? 0.01),
                'API_V1_RETORNAR_APARICOES' => (float) ($configs['escavador_price_api_v1_retornar_aparicoes'] ?? 0.01),
                'API_V1_REMOVER_MONITORAMENTO' => (float) ($configs['escavador_price_api_v1_remover_monitoramento'] ?? 0.01),
                'API_V1_CRIAR_MONITORAMENTO' => (float) ($configs['escavador_price_api_v1_criar_monitoramento'] ?? 0.01),
                'API_V1_TESTAR_CALLBACK' => (float) ($configs['escavador_price_api_v1_testar_callback'] ?? 0.01),
                'API_V1_DIARIOS_MONITORADOS' => (float) ($configs['escavador_price_api_v1_diarios_monitorados'] ?? 0.01),
                'API_V2_PROCESSOSDOENVOLVIDOPORCPFCNPJOUNOME' => (float) ($configs['escavador_price_api_v2_processosdoenvolvidoporcpfcnpjounome'] ?? 4.50),
                'API_V2_PROCESSOSDEUMADVOGADOPOROAB' => (float) ($configs['escavador_price_api_v2_processosdeumadvogadoporoab'] ?? 4.50),
                'API_V2_PROCESSOPORNUMERAOCNJCAPA' => (float) ($configs['escavador_price_api_v2_processopornumeraocnjcapa'] ?? 0.01),
                'API_V2_MOVIMENTAESDEUMPROCESSO' => (float) ($configs['escavador_price_api_v2_movimentaesdeumprocesso'] ?? 0.05),
                'API_V2_STATUS_ATUALIZACAO' => (float) ($configs['escavador_price_api_v2_status_atualizacao'] ?? 0.01),
                'API_V2_SOLICITARATUALIZAODEUMPROCESSO' => (float) ($configs['escavador_price_api_v2_solicitaratualizaodeumprocesso'] ?? 0.01),
                'API_V2_TRIBUNAIS_DISPONIVEIS' => (float) ($configs['escavador_price_api_v2_tribunais_disponiveis'] ?? 0.01),
                'API_V2_RESUMO_OAB' => (float) ($configs['escavador_price_api_v2_resumo_oab'] ?? 0.40),
                'API_V2_RESUMO_ENVOLVIDO' => (float) ($configs['escavador_price_api_v2_resumo_envolvido'] ?? 0.40),
                'API_V2_AUTOS_PROCESSO' => (float) ($configs['escavador_price_api_v2_autos_processo'] ?? 0.01),
                'API_V2_DOCS_PUBLICOS' => (float) ($configs['escavador_price_api_v2_docs_publicos'] ?? 0.01),
                'API_V2_ENVOLVIDOS_PROCESSO' => (float) ($configs['escavador_price_api_v2_envolvidos_processo'] ?? 0.05),
                'API_V2_RESUMO_IA_PROCESSO' => (float) ($configs['escavador_price_api_v2_resumo_ia_processo'] ?? 0.01),
                'API_V2_STATUS_RESUMO_IA_UI' => (float) ($configs['escavador_price_api_v2_status_resumo_ia_ui'] ?? 0.01),
                'API_V2_SISTEMAS_DISPONIVEIS' => (float) ($configs['escavador_price_api_v2_sistemas_disponiveis'] ?? 0.01),
                'API_V2_CALLBACKS_LISTAR' => (float) ($configs['escavador_price_api_v2_callbacks_listar'] ?? 0.01),
                'BUSCA_JURIS' => (float) ($configs['escavador_price_busca_juris'] ?? 0.02),
                'BUSCA_DIARIO' => (float) ($configs['escavador_price_busca_diario'] ?? 0.03),
                'BUSCA_OAB_PAGA' => (float) ($configs['escavador_price_busca_oab_paga'] ?? 0.03),
                'DOC_JURIS' => (float) ($configs['escavador_price_doc_juris'] ?? 0.04),
                'PDF_JURIS' => (float) ($configs['escavador_price_pdf_juris'] ?? 0.07),
                'BUSCA_LEGIS' => (float) ($configs['escavador_price_busca_legis'] ?? 0.03),
                'DOC_LEGIS' => (float) ($configs['escavador_price_doc_legis'] ?? 0.03),
                'FRAG_LEGIS' => (float) ($configs['escavador_price_frag_legis'] ?? 0.03),
                'AUTOS_DOCS_ESP' => (float) ($configs['escavador_price_autos_docs_esp'] ?? 0.75),
                'ASYNC_RESULTADOS' => (float) ($configs['escavador_price_async_resultados'] ?? 0.01),
                'ASYNC_RESULTADO_ID' => (float) ($configs['escavador_price_async_resultado_id'] ?? 0.01),
                'CALLBACKS_MARCAR_RECEBIDOS' => (float) ($configs['escavador_price_callbacks_marcar_recebidos'] ?? 0.01),
                'CALLBACKS_LISTAR' => (float) ($configs['escavador_price_callbacks_listar'] ?? 0.01),
                'CALLBACKS_REENVIAR' => (float) ($configs['escavador_price_callbacks_reenviar'] ?? 0.01),
                'MONITORAMENTOS_LISTAR' => (float) ($configs['escavador_price_monitoramentos_listar'] ?? 0.01),
                'MONITORAMENTOS_ID' => (float) ($configs['escavador_price_monitoramentos_id'] ?? 0.01),
                'MONITORAMENTOS_EDITAR' => (float) ($configs['escavador_price_monitoramentos_editar'] ?? 0.01),
                'MONITORAMENTOS_REMOVER' => (float) ($configs['escavador_price_monitoramentos_remover'] ?? 0.01),
                'MONITORAMENTOS_APARICOES' => (float) ($configs['escavador_price_monitoramentos_aparicoes'] ?? 0.01),
                'CRIAR_MON_DIARIOS' => (float) ($configs['escavador_price_criar_mon_diarios'] ?? 0.01),
                'CRIAR_MON_TRIBUNAL' => (float) ($configs['escavador_price_criar_mon_tribunal'] ?? 0.01),
                'CRIAR_MON_PROCESSO_V2' => (float) ($configs['escavador_price_criar_mon_processo_v2'] ?? 0.01),
                'CRIAR_MON_NOVOS_PROCESSO_V2' => (float) ($configs['escavador_price_criar_mon_novos_processo_v2'] ?? 0.01),
                'STATUS_ATUALIZACAO_PROCESSO' => (float) ($configs['escavador_price_status_atualizacao_processo'] ?? 0.01),
                'CALLBACKS_LISTAR_V2' => (float) ($configs['escavador_price_callbacks_listar_v2'] ?? 0.01),
                'CALLBACKS_MARCAR_RECEBIDOS_V2' => (float) ($configs['escavador_price_callbacks_marcar_recebidos_v2'] ?? 0.01),
                'CALLBACKS_REENVIAR_V2' => (float) ($configs['escavador_price_callbacks_reenviar_v2'] ?? 0.01),
                'MONITORAMENTO_NOVOS_PROCESSO_LISTAR' => (float) ($configs['escavador_price_monitoramento_novos_processo_listar'] ?? 0.01),
                'MONITORAMENTO_NOVOS_PROCESSO_ID' => (float) ($configs['escavador_price_monitoramento_novos_processo_id'] ?? 0.01),
                'MONITORAMENTO_NOVOS_PROCESSO_REMOVER' => (float) ($configs['escavador_price_monitoramento_novos_processo_remover'] ?? 0.01),
                'MONITORAMENTO_NOVOS_PROCESSO_RESULTADOS' => (float) ($configs['escavador_price_monitoramento_novos_processo_resultados'] ?? 0.01),
                'MONITORAMENTO_NOVOS_PROCESSO_EDITAR' => (float) ($configs['escavador_price_monitoramento_novos_processo_editar'] ?? 0.01),
                'MONITORAMENTO_PROCESSO_LISTAR' => (float) ($configs['escavador_price_monitoramento_processo_listar'] ?? 0.01),
                'MONITORAMENTO_PROCESSO_ID' => (float) ($configs['escavador_price_monitoramento_processo_id'] ?? 0.01),
                'MONITORAMENTO_PROCESSO_REMOVER' => (float) ($configs['escavador_price_monitoramento_processo_remover'] ?? 0.01),
                'STATUS_RESUMO_IA' => (float) ($configs['escavador_price_status_resumo_ia'] ?? 0.01),
                'CERTIFICADOS_DIGITAIS' => (float) ($configs['escavador_price_certificados_digitais'] ?? 0.01),
                'GERENCIAR_WEBHOOKS_V2' => 0.00,
            ];
        });
    }
}
