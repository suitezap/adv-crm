<?php

namespace SuiteZap\LawFirm\Legal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use SuiteZap\LawFirm\Financial\Models\Financial;
use SuiteZap\LawFirm\Escavador\Models\EscavadorProcesso;
use SuiteZap\LawFirm\Escavador\Models\EscavadorMonitoramento;
class Processo extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'processos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'numero_cnj',
        'protocolo_distribuicao',
        'titulo',
        'descricao',
        'tribunal',
        'vara',
        'juiz_atual',
        'comarca',
        'valor_causa',
        'parte_contraria',
        'advogado_parte_contraria',
        'fase_processual',
        'status',
        'link_acesso',
        'person_id',
        'organization_id',
        'user_id',
        'lead_id',
        'caso_id',
        'data_distribuicao',
        'data_audiencia',
        'area_direito',
        'probabilidade_exito',
        'tipo_parte',
        'cpf_cnpj',
        'advogado_oab',
        'whatsapp_advogado_contrario',
        'email_advogado_contrario',
        'subarea_direito',
        // New Fields Added (Sprint 1 Refinement)
        'opposing_party_name',
        'opposing_party_type',
        'opposing_party_document',
        'link_audiencia', // hearing_link identified in view
        'advogado_responsavel_nome',
        'advogado_responsavel_oab',

        'envolvidos_escavador',
    ];

    /**
     * Mutator: Data Distribuicao
     * Fix 1900 issue by converting empty strings to null
     */
    public function setDataDistribuicaoAttribute($value)
    {
        $this->attributes['data_distribuicao'] = $value ?: null;
    }

    /**
     * Mutator: Data Audiencia
     * Fix 1900 issue by converting empty strings to null
     */
    public function setDataAudienciaAttribute($value)
    {
        $this->attributes['data_audiencia'] = $value ?: null;
    }

    /**
     * Set the valor_causa attribute.
     * Cleans "R$ 1.200,50" to 1200.50
     *
     * @param  string  $value
     * @return void
     */
    public function setValorCausaAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['valor_causa'] = null;
            return;
        }

        // Fix: Check if value is already numeric (float/int)
        // If it is, don't try to strip formatting again.
        if (is_numeric($value)) {
            $this->attributes['valor_causa'] = $value;
            return;
        }

        // Remove R$, spaces, and dots (thousands separator)
        $clean = str_replace(['R$', ' ', '.'], '', $value);

        // Replace comma with dot (decimal separator)
        $clean = str_replace(',', '.', $clean);

        $this->attributes['valor_causa'] = (float) $clean;
    }



    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data_distribuicao' => 'date',
        'data_audiencia' => 'datetime',
    ];

    /**
     * Get the lead associated with the processo.
     *
     * @return BelongsTo
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Lead\Models\Lead::class);
    }

    /**
     * Get the person (client) associated with the processo.
     *
     * @return BelongsTo
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Contact\Models\Person::class);
    }

    /**
     * Get the organization (company) associated with the processo.
     *
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(\Webkul\Contact\Models\Organization::class);
    }

    /**
     * Get the caso (case) this processo belongs to.
     *
     * @return BelongsTo
     */
    public function caso(): BelongsTo
    {
        return $this->belongsTo(Caso::class);
    }

    /**
     * Get the prazos (deadlines) for the processo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prazos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Prazo::class);
    }

    /**
     * Get the notes (notas) for the processo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProcessoNota::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the financials (revenues/expenses) for the processo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function financeiros(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Financial::class);
    }

    /**
     * Get the responsible lawyer (user).
     *
     * @return BelongsTo
     */
    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(\Webkul\User\Models\User::class, 'user_id');
    }

    /**
     * Get Total Revenue (Receita) - Excluding Cancelled.
     * Robust check: trim, lowercase, float cast.
     */
    public function getReceitaTotalAttribute()
    {
        return $this->financeiros
            ->filter(function ($item) {
                // Remove espaços e joga pra minúsculo
                $tipo = strtolower(trim($item->tipo));
                $status = strtolower(trim($item->status));

                // Verifica se contém a palavra 'receita' (mais seguro que igualdade estrita)
                return str_contains($tipo, 'receita')
                    && $status !== 'cancelado';
            })
            ->sum(function ($item) {
                return (float) $item->valor;
            });
    }

    /**
     * Get Total Expenses (Despesas) - Excluding Cancelled.
     * Robust check: trim, lowercase, float cast.
     */
    public function getDespesasTotaisAttribute()
    {
        return $this->financeiros
            ->filter(function ($item) {
                $tipo = strtolower(trim($item->tipo));
                $status = strtolower(trim($item->status));
                return str_contains($tipo, 'despesa')
                    && $status !== 'cancelado';
            })
            ->sum(function ($item) {
                return (float) $item->valor;
            });
    }

    /**
     * Get Net Profit (Lucro Líquido).
     */
    public function getLucroLiquidoAttribute()
    {
        return $this->receita_total - $this->despesas_totais;
    }

    /**
     * Get Profit Margin (Margem de Lucratividade).
     */
    public function getMargemLucratividadeAttribute()
    {
        if ($this->receita_total == 0)
            return 0;
        return ($this->lucro_liquido / $this->receita_total) * 100;
    }

    /**
     * Get Success Index (Índice de Êxito).
     */
    public function getIndiceExitoAttribute()
    {
        if ($this->valor_causa == 0)
            return 0;
        return ($this->receita_total / $this->valor_causa) * 100;
    }
    /**
     * Get the CSS class for audience date alert.
     * Logic:
     * - Default: Gray
     * - Past/Today (Ativo/Suspenso): Red + Pulse
     * - Within 5 days (Ativo/Suspenso): Orange
     * - Future (>5 days) (Ativo/Suspenso): Emerald
     *
     * @return string
     */
    public function getAudienciaAlertClassAttribute(): string
    {
        // Default styling
        $defaultClass = "text-gray-600 dark:text-gray-400";

        if (!$this->data_audiencia) {
            return $defaultClass;
        }

        // Only apply alerts for non-closed processes.
        // Any pipeline stage other than 'Encerrado' is considered active.
        $status = $this->status;
        if (strcasecmp($status, 'Encerrado') === 0) {
            return $defaultClass;
        }

        $audiencia = \Carbon\Carbon::parse($this->data_audiencia)->startOfDay();
        $hoje = \Carbon\Carbon::now()->startOfDay();
        $diffDays = $hoje->diffInDays($audiencia, false);

        if ($diffDays <= 0) {
            // Overdue or Today
            return "text-red-800 bg-red-100 px-2 py-0.5 rounded font-bold animate-pulse";
        } elseif ($diffDays <= 5) {
            // Urgency Warning
            return "text-orange-600 font-bold";
        } else {
            // Safe
            return "text-emerald-600 font-medium";
        }
    }
    /**
     * Get the attachments (GED) for the processo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function anexos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Anexo::class);
    }

    /**
     * Get the documents (GED) for the processo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\SuiteZap\LawFirm\GED\Models\ProcessDocument::class);
    }

    /**
     * Get the checklists for the process.
     */
    public function checklists()
    {
        return $this->hasMany(\SuiteZap\LawFirm\Legal\Models\CaseChecklist::class, 'processo_id');
    }

    /**
     * Get the imported WhatsApp messages for the processo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function whatsappMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProcessoWhatsappMessage::class)->orderBy('message_timestamp', 'asc');
    }

    /**
     * Get the WhatsApp import sessions for the processo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function whatsappImports(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WhatsappImport::class, 'processo_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get the escavador processo mirror data.
     *
     * @return HasOne
     */
    public function escavadorProcesso(): HasOne
    {
        return $this->hasOne(EscavadorProcesso::class, 'processo_id');
    }

    /**
     * Get the escavador monitoramentos linked to this process.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function escavadorMonitoramentos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EscavadorMonitoramento::class, 'processo_id');
    }
}
