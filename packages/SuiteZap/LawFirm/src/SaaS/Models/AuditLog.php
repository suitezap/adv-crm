<?php

namespace SuiteZap\LawFirm\SaaS\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'lawfirm_audit_logs';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'meta_data'  => 'array',
        'created_at' => 'datetime',
    ];
}
