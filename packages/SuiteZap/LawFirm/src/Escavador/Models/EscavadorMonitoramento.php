<?php

namespace SuiteZap\LawFirm\Escavador\Models;

use Illuminate\Database\Eloquent\Model;

class EscavadorMonitoramento extends Model
{
    protected $table = 'escavador_monitoramentos';

    protected $fillable = [
        'tenant_id',
        'external_id',
        'type',
        'query_value',
        'frequency',
        'notify_whatsapp',
    ];

    protected $casts = [
        'notify_whatsapp' => 'boolean',
    ];
}
