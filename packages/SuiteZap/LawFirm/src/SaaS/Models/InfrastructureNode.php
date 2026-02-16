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
        'status'
    ];
}
