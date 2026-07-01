<?php

namespace SuiteZap\LawFirm\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

class InfrastructureNode extends Model
{
    protected $connection = 'mothership';

    protected $table = 'infrastructure_nodes';

    protected $fillable = [
        'name',
        'type',
        'base_url',
        'api_key',
        'capacity_limit',
        'current_load',
        'status',
        'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];

    /**
     * Tenants que usam este nó como Evolution API.
     */
    public function tenantsAsEvolution()
    {
        return $this->hasMany(Tenant::class, 'evolution_node_id');
    }

    /**
     * Tenants que usam este nó como Storage (S3/MinIO).
     */
    public function tenantsAsStorage()
    {
        return $this->hasMany(Tenant::class, 'storage_node_id');
    }

    /**
     * Tenants que usam este nó como N8N.
     */
    public function tenantsAsN8n()
    {
        return $this->hasMany(Tenant::class, 'n8n_node_id');
    }
}
