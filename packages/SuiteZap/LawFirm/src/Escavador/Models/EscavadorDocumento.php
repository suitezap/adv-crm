<?php

namespace SuiteZap\LawFirm\Escavador\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscavadorDocumento extends Model
{
    protected $table = 'escavador_documentos';

    protected $fillable = [
        'escavador_processo_id',
        'tipo',
        'escavador_id',
        'url_pdf',
        'fonte',
        'data_extracao',
        'raw_json',
    ];

    protected $casts = [
        'data_extracao' => 'datetime',
        'raw_json'      => 'array',
    ];

    public function escavadorProcesso(): BelongsTo
    {
        return $this->belongsTo(EscavadorProcesso::class, 'escavador_processo_id');
    }
}
