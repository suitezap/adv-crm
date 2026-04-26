<?php

namespace SuiteZap\LawFirm\Escavador\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscavadorMovimentacao extends Model
{
    protected $table = 'escavador_movimentacoes';

    protected $fillable = [
        'escavador_processo_id',
        'data_movimentacao',
        'texto_movimentacao',
        'escavador_id',
        'tipo',
        'raw_json',
    ];

    protected $casts = [
        'data_movimentacao' => 'date',
        'raw_json' => 'array',
    ];

    public function escavadorProcesso(): BelongsTo
    {
        return $this->belongsTo(EscavadorProcesso::class, 'escavador_processo_id');
    }
}
