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
     * Verifica se pode criar novos usuários
     */
    public static function canCreateUser(): bool
    {
        $subscription = self::getCurrentSubscription();

        if (!$subscription) {
            Log::warning('SAAS: Bloqueio de criação de usuário — assinatura não localizada.');
            return false;
        }

        $currentCount = \Webkul\User\Models\User::where('status', 1)->count();
        $limit = $subscription->max_users;

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
            'token' => $tenantConfig->evolution_api_key
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
            'filesystems.disks.s3.bucket' => $tenantConfig->minio_bucket_name ?? ($metaData['bucket'] ?? 'lawfirm-fallback'),
            'filesystems.disks.s3.endpoint' => rtrim($storageNode->base_url, '/'),
            'filesystems.disks.s3.use_path_style_endpoint' => $metaData['use_path_style_endpoint'] ?? true,
            'filesystems.disks.s3.throw' => false,
        ]);

        Log::debug("SAAS: Storage configurado dinamicamente para nó {$storageNode->id}");
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
                ->whereIn('key', [
                    // Existing
                    'escavador_price_capa',
                    'escavador_price_diario',
                    'escavador_price_busca',
                    'escavador_price_resumo',
                    'escavador_price_busca_juris',
                    'escavador_price_busca_diario',
                    'escavador_price_info_inst',
                    'escavador_price_info_pessoa',
                    'escavador_price_busca_oab',
                    'escavador_price_atualizar_processo',
                    'escavador_price_baixar_autos',
                    // New V1
                    'escavador_price_pagina_diario',
                    'escavador_price_pessoas_instituicao',
                    'escavador_price_processos_instituicao',
                    'escavador_price_doc_juris',
                    'escavador_price_pdf_juris',
                    'escavador_price_busca_legis',
                    'escavador_price_doc_legis',
                    'escavador_price_frag_legis',
                    'escavador_price_mov_processo_diario',
                    'escavador_price_detalhes_pessoa',
                    'escavador_price_processos_pessoa',
                    'escavador_price_autos_docs_esp',
                    'escavador_price_busca_proc_diario_oab',
                    'escavador_price_busca_proc_diario_num',
                    'escavador_price_envolvidos_proc_diario',
                    'escavador_price_mov_proc_diario',
                    'escavador_price_proc_diario',
                    // New V2
                    'escavador_price_atualizacao_processo_docs',
                    'escavador_price_atualizacao_processo_autos',
                    'escavador_price_atualizacao_processo_pub',
                    'escavador_price_processos_envolvido_cpf',
                    'escavador_price_processos_advogado_oab',
                    'escavador_price_resumo_advogado_oab',
                    'escavador_price_resumo_envolvido',
                    'escavador_price_documentos_publicos',
                    'escavador_price_envolvidos_processo',
                    'escavador_price_movimentacoes_processo'
                ])
                ->pluck('value', 'key');

            return [
                // Existing
                'CAPA_PROCESSO' => (float) ($configs['escavador_price_capa'] ?? 3.00),
                'PDF_DIARIO' => (float) ($configs['escavador_price_diario'] ?? 3.00),
                'BUSCA_TERMO' => (float) ($configs['escavador_price_busca'] ?? 3.00),
                'RESUMO_IA' => (float) ($configs['escavador_price_resumo'] ?? 0.08),
                'BUSCA_JURIS' => (float) ($configs['escavador_price_busca_juris'] ?? 3.00),
                'BUSCA_DIARIO' => (float) ($configs['escavador_price_busca_diario'] ?? 3.00),
                'INFO_INSTITUICAO' => (float) ($configs['escavador_price_info_inst'] ?? 3.00),
                'INFO_PESSOA' => (float) ($configs['escavador_price_info_pessoa'] ?? 3.00),
                'BUSCA_OAB_PAGA' => (float) ($configs['escavador_price_busca_oab'] ?? 3.00),
                'ATUALIZAR_PROCESSO' => (float) ($configs['escavador_price_atualizar_processo'] ?? 3.00),
                'BAIXAR_AUTOS' => (float) ($configs['escavador_price_baixar_autos'] ?? 0.18),

                // New V1
                'PAGINA_DIARIO' => (float) ($configs['escavador_price_pagina_diario'] ?? 3.00),
                'PESSOAS_INSTITUICAO' => (float) ($configs['escavador_price_pessoas_instituicao'] ?? 3.00),
                'PROCESSOS_INSTITUICAO' => (float) ($configs['escavador_price_processos_instituicao'] ?? 3.00),
                'DOC_JURIS' => (float) ($configs['escavador_price_doc_juris'] ?? 3.00),
                'PDF_JURIS' => (float) ($configs['escavador_price_pdf_juris'] ?? 3.00),
                'BUSCA_LEGIS' => (float) ($configs['escavador_price_busca_legis'] ?? 3.00),
                'DOC_LEGIS' => (float) ($configs['escavador_price_doc_legis'] ?? 3.00),
                'FRAG_LEGIS' => (float) ($configs['escavador_price_frag_legis'] ?? 3.00),
                'MOV_PROCESSO_DIARIO' => (float) ($configs['escavador_price_mov_processo_diario'] ?? 3.00),
                'DETALHES_PESSOA' => (float) ($configs['escavador_price_detalhes_pessoa'] ?? 3.00),
                'PROCESSOS_PESSOA' => (float) ($configs['escavador_price_processos_pessoa'] ?? 3.00),
                'AUTOS_DOCS_ESP' => (float) ($configs['escavador_price_autos_docs_esp'] ?? 0.75),
                'BUSCA_PROC_DIARIO_OAB' => (float) ($configs['escavador_price_busca_proc_diario_oab'] ?? 3.00),
                'BUSCA_PROC_DIARIO_NUM' => (float) ($configs['escavador_price_busca_proc_diario_num'] ?? 3.00),
                'ENVOLVIDOS_PROC_DIARIO' => (float) ($configs['escavador_price_envolvidos_proc_diario'] ?? 3.00),
                'MOV_PROC_DIARIO' => (float) ($configs['escavador_price_mov_proc_diario'] ?? 3.00),
                'PROC_DIARIO' => (float) ($configs['escavador_price_proc_diario'] ?? 3.00),

                // New V2
                'ATUALIZACAO_PROCESSO_DOCS' => (float) ($configs['escavador_price_atualizacao_processo_docs'] ?? 0.75),
                'ATUALIZACAO_PROCESSO_AUTOS' => (float) ($configs['escavador_price_atualizacao_processo_autos'] ?? 1.50),
                'ATUALIZACAO_PROCESSO_PUB' => (float) ($configs['escavador_price_atualizacao_processo_pub'] ?? 0.20),
                'PROCESSOS_ENVOLVIDO_CPF' => (float) ($configs['escavador_price_processos_envolvido_cpf'] ?? 3.00),
                'PROCESSOS_ADVOGADO_OAB' => (float) ($configs['escavador_price_processos_advogado_oab'] ?? 3.00),
                'RESUMO_ADVOGADO_OAB' => (float) ($configs['escavador_price_resumo_advogado_oab'] ?? 3.00),
                'RESUMO_ENVOLVIDO' => (float) ($configs['escavador_price_resumo_envolvido'] ?? 3.00),
                'DOCUMENTOS_PUBLICOS' => (float) ($configs['escavador_price_documentos_publicos'] ?? 0.06),
                'ENVOLVIDOS_PROCESSO' => (float) ($configs['escavador_price_envolvidos_processo'] ?? 0.05),
                'MOVIMENTACOES_PROCESSO' => (float) ($configs['escavador_price_movimentacoes_processo'] ?? 3.00),
                'GERENCIAR_WEBHOOKS_V2'    => 0.00, // Gratuito — listagem e cadastro de URLs de callback
                'CERTIFICADOS_DIGITAIS'    => 0.00, // Gratuito — gerenciamento de certificados digitais

                // === Novos V1 e V2 (Gratuitos) ===
                'ASYNC_RESULTADOS' => 0.00,
                'ASYNC_RESULTADO_ID' => 0.00,
                'CALLBACKS_MARCAR_RECEBIDOS' => 0.00,
                'CALLBACKS_LISTAR' => 0.00,
                'CALLBACKS_REENVIAR' => 0.00,
                'DIARIOS_ORIGENS' => 0.00,
                'MONITORAMENTO_DIARIOS_ORIGENS' => 0.00,
                'MONITORAMENTOS_LISTAR' => 0.00,
                'MONITORAMENTOS_ID' => 0.00,
                'MONITORAMENTOS_EDITAR' => 0.00,
                'MONITORAMENTOS_REMOVER' => 0.00,
                'MONITORAMENTOS_APARICOES' => 0.00,
                'MONITORAMENTOS_TESTAR_CALLBACK' => 0.00,
                'MONITORAMENTOS_TRIBUNAL_LISTAR' => 0.00,
                'MONITORAMENTOS_TRIBUNAL_ID' => 0.00,
                'MONITORAMENTOS_TRIBUNAL_EDITAR' => 0.00,
                'MONITORAMENTOS_TRIBUNAL_REMOVER' => 0.00,
                'SALDO_V1' => 0.00,
                'TRIBUNAIS_SISTEMAS' => 0.00,
                'TRIBUNAL_POR_ORIGEM' => 0.00,
                'ORGAOS_ADMIN_SISTEMAS' => 0.00,

                'STATUS_ATUALIZACAO_PROCESSO' => 0.00,
                'CALLBACKS_LISTAR_V2' => 0.00,
                'CALLBACKS_MARCAR_RECEBIDOS_V2' => 0.00,
                'CALLBACKS_REENVIAR_V2' => 0.00,
                'MONITORAMENTO_NOVOS_PROCESSO_LISTAR' => 0.00,
                'MONITORAMENTO_NOVOS_PROCESSO_ID' => 0.00,
                'MONITORAMENTO_NOVOS_PROCESSO_REMOVER' => 0.00,
                'MONITORAMENTO_NOVOS_PROCESSO_RESULTADOS' => 0.00,
                'MONITORAMENTO_NOVOS_PROCESSO_EDITAR' => 0.00,
                'MONITORAMENTO_PROCESSO_LISTAR' => 0.00,
                'MONITORAMENTO_PROCESSO_ID' => 0.00,
                'MONITORAMENTO_PROCESSO_REMOVER' => 0.00,
                'STATUS_RESUMO_IA' => 0.00,
                'TRIBUNAIS_LISTAR' => 0.00,
                'SISTEMAS_TRIBUNAIS_LISTAR' => 0.00,

                // === Criação de Monitoramentos (Macro Buttons) ===
                'CRIAR_MON_DIARIOS'          => 3.00, // R$ 3,00 / mês — POST api/v1/monitoramentos
                'CRIAR_MON_TRIBUNAL'         => 3.00, // R$ 3,00 / mês — POST api/v1/monitoramentos-tribunal
                'CRIAR_MON_PROCESSO_V2'      => 3.00, // R$ 3,00 / mês — POST api/v2/monitoramentos/processos
                'CRIAR_MON_NOVOS_PROCESSO_V2'=> 3.00, // R$ 3,00 / mês — POST api/v2/monitoramentos/novos-processos
            ];
        });
    }
}
