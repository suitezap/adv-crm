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
}

