<?php

namespace SuiteZap\LawFirm\Escavador\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SuiteZap\LawFirm\Legal\Models\Processo;

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
        'status',
        'processo_id',
        'custo_mensal',
        'nome_alvo',
        'ultima_notificacao_at',
    ];

    protected $casts = [
        'notify_whatsapp'       => 'boolean',
        'custo_mensal'          => 'float',
        'ultima_notificacao_at' => 'datetime',
    ];

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }

    public function isAtivo(): bool
    {
        return $this->status === 'ativo';
    }

    public function isPausado(): bool
    {
        return $this->status === 'pausado';
    }
}
