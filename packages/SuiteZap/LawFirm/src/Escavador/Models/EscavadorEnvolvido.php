<?php

namespace SuiteZap\LawFirm\Escavador\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscavadorEnvolvido extends Model
{
    protected $table = 'escavador_envolvidos';

    protected $fillable = [
        'escavador_processo_id',
        'nome',
        'cpf_cnpj',
        'tipo_participacao',
        'oab',
        'escavador_id',
        'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
    ];

    public function escavadorProcesso(): BelongsTo
    {
        return $this->belongsTo(EscavadorProcesso::class, 'escavador_processo_id');
    }
}
