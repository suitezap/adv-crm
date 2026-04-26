<?php

namespace SuiteZap\LawFirm\Escavador\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SuiteZap\LawFirm\Legal\Models\Processo;

class EscavadorProcesso extends Model
{
    protected $table = 'escavador_processos';

    protected $fillable = [
        'tenant_id',
        'processo_id',
        'numero_cnj',
        'tribunal',
        'vara',
        'segredo_justica',
        'resumo_ia',
        'status_atualizacao',
        'escavador_id',
        'capa_json',
        'data_ultima_verificacao',
    ];

    protected $casts = [
        'segredo_justica' => 'boolean',
        'capa_json' => 'array',
        'data_ultima_verificacao' => 'datetime',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(EscavadorMovimentacao::class, 'escavador_processo_id')
            ->orderBy('data_movimentacao', 'desc');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(EscavadorDocumento::class, 'escavador_processo_id');
    }

    public function envolvidos(): HasMany
    {
        return $this->hasMany(EscavadorEnvolvido::class, 'escavador_processo_id');
    }

    public function requests(): HasMany
    {
        // Link pelo processo_id na escavador_requests
        return $this->hasMany(EscavadorRequest::class, 'processo_id', 'processo_id')
            ->orderBy('created_at', 'desc');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function isAtualizado(): bool
    {
        return $this->status_atualizacao === 'atualizado';
    }

    public function needsRefresh(int $hours = 24): bool
    {
        if (!$this->data_ultima_verificacao) {
            return true;
        }

        return $this->data_ultima_verificacao->diffInHours(now()) >= $hours;
    }

    public function getResumoExcerpt(int $chars = 200): ?string
    {
        if (!$this->resumo_ia) {
            return null;
        }

        if (mb_strlen($this->resumo_ia) <= $chars) {
            return $this->resumo_ia;
        }

        return mb_substr($this->resumo_ia, 0, $chars) . '...';
    }
}
