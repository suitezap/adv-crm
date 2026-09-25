<?php

namespace SuiteZap\LawFirm\Legal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Contact\Models\Organization;
use Webkul\Contact\Models\Person;

class ProcessoParticipante extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'law_processo_participantes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'processo_id',
        'person_id',
        'organization_id',
        'tipo_parte',
    ];

    /**
     * Get the processo.
     */
    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class);
    }

    /**
     * Get the person (if PF).
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get the organization (if PJ).
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
