<?php

namespace SuiteZap\LawFirm\Escavador\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SuiteZap\LawFirm\Legal\Models\Processo;

/**
 * EscavadorRequest — Registro de controle de requisições à API do Escavador.
 *
 * Resolve a assincronicidade da V2:
 *   POST → 202 Accepted (external_id) → Webhook → status=completed
 *
 * @property int         $id
 * @property string      $tenant_id
 * @property int|null    $processo_id
 * @property string|null $external_id
 * @property string      $endpoint_type
 * @property string      $status          'pending' | 'completed' | 'failed'
 * @property float       $cost
 * @property array|null  $payload_response
 */
class EscavadorRequest extends Model
{
    protected $table = 'escavador_requests';

    protected $fillable = [
        'tenant_id',
        'processo_id',
        'external_id',
        'request_hash',
        'endpoint_type',
        'status',
        'cost',
        'payload_response',
    ];

    protected $casts = [
        'payload_response' => 'array',
        'cost' => 'float',
    ];

    // ─── Status Constants ──────────────────────────────────────────────────────

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    // ─── Relationships ─────────────────────────────────────────────────────────

    /**
     * Processo jurídico vinculado (opcional).
     */
    public function processo(): BelongsTo
    {
        return $this->belongsTo(Processo::class, 'processo_id');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function markCompleted(array $payload): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'payload_response' => $payload,
        ]);
    }

    public function markFailed(array $payload = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'payload_response' => $payload ?: null,
        ]);
    }
}
