<?php

namespace SuiteZap\LawFirm\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Lead\Models\Lead;

class LeadTriagem extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lead_triagem';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'lead_id',
        'area',
        'assunto',
        'urgencia',
        'tipo',
        'tipo_agente',
        'objetivo',
        'risco',
        'probabilidade',
        'recomendacao',
        'competencia',
        'risco_operacional',
        'informacoes_faltantes',
        'perguntas_chave',
        'tipo_atuacao',
        'complexidade',
        'modelo_honorarios',
        'estrategia_cobranca',
        'argumentacao_valor',
        'abordagem_abertura',
        'estrategia_objecoes',
        'frase_fechamento',
        'cta',
    ];

    /**
     * Get the lead that owns the triagem.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
