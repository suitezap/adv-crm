<?php

namespace SuiteZap\LawFirm\Legal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessoNota extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'processo_notas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'processo_id',
        'user_id',
        'nota',
    ];

    /**
     * Get the processo that owns the data.
     */
    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }

    /**
     * Get the user that created the note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Webkul\User\Models\User::class, 'user_id');
    }
}
