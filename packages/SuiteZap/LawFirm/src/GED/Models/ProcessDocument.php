<?php

namespace SuiteZap\LawFirm\GED\Models;

use Illuminate\Database\Eloquent\Model;
use SuiteZap\LawFirm\Legal\Models\Processo;

class ProcessDocument extends Model
{
    protected $table = 'law_process_documents';
    protected $fillable = [
        'processo_id',
        'name',
        'status',
        'file_path',
        'notes'
    ];

    public function process()
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }
}
