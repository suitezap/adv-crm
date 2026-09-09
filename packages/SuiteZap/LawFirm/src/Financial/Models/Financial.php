<?php

namespace SuiteZap\LawFirm\Financial\Models;

use Illuminate\Database\Eloquent\Model;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\SaaS\Concerns\BelongsToTenant;

class Financial extends Model
{
    use BelongsToTenant;

    protected $table = 'law_financials';

    protected $fillable = [
        'tenant_id',
        'processo_id',
        'tipo',
        'nome',
        'valor',
        'data_vencimento',
        'status',
        'descricao',
        // Novos campos para métricas avançadas
        'issued_at',
        'payment_date',
        'category',
        'payment_method',
    ];

    protected $casts = [
        'data_vencimento' => 'date',
        'issued_at'       => 'date',
        'payment_date'    => 'date',
        'valor'           => 'decimal:2',
    ];

    public function processo()
    {
        return $this->belongsTo(Processo::class);
    }
}
