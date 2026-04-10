<?php

namespace SuiteZap\LawFirm\SaaS\Models;

use Illuminate\Database\Eloquent\Model;
class Tenant extends Model
{

    protected $connection = 'mothership';
    protected $table = 'tenants';

    // Importante: O ID não é auto-incremento, é uma string (ex: 'lawfirm_tenant_1')
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'classification',
        'internal_notes',
        'n8n_node_id',
        'evolution_node_id',
        'storage_node_id',
        'evolution_instance_name',
        'evolution_api_key',
        'minio_bucket_name',
        'asaas_node_id'
    ];

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'tenant_id', 'id');
    }

    public function evolutionNode()
    {
        return $this->belongsTo(InfrastructureNode::class, 'evolution_node_id');
    }

    public function storageNode()
    {
        return $this->belongsTo(InfrastructureNode::class, 'storage_node_id');
    }

    public function n8nNode()
    {
        return $this->belongsTo(InfrastructureNode::class, 'n8n_node_id');
    }

    public function asaasNode()
    {
        return $this->belongsTo(InfrastructureNode::class, 'asaas_node_id');
    }
}
