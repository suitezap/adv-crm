<?php

namespace SuiteZap\LawFirm\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $connection = 'mothership';
    protected $table = 'subscriptions';

    protected $fillable = [
        'tenant_id',
        'status',
        'expires_at',
        'max_users',
        'storage_limit_gb',
        'current_usage_bytes',
        'ai_tokens_balance',
        'active_modules'
    ];

    protected $casts = [
        'active_modules' => 'array',
        'expires_at' => 'date'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}
