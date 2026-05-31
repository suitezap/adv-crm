<?php

namespace SuiteZap\LawFirm\GED\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SuiteZap\LawFirm\Legal\Models\Caso;
use SuiteZap\LawFirm\Legal\Models\Processo;

class ProcessDocument extends Model
{
    protected $table = 'law_process_documents';
    protected $fillable = [
        'processo_id',
        'caso_id',
        'name',
        'status',
        'file_path',
        'notes'
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }

    /**
     * Get the caso (case) this document belongs to.
     * Enables Zero-Copy document sharing across sibling processos.
     */
    public function caso(): BelongsTo
    {
        return $this->belongsTo(Caso::class);
    }
}
