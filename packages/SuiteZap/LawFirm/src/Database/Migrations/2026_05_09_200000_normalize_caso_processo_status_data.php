<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data Migration: Normalize legacy status values in law_casos and processos.
 *
 * Maps old status strings ('Ativo', 'aberto', etc.) to the new
 * 12-stage canonical pipeline defined in LegalOrchestrator::VALID_STATUSES.
 *
 * Safe to run multiple times (idempotent WHERE conditions).
 */
return new class extends Migration
{
    /**
     * Mapping: [old_value => new_value]
     */
    private const STATUS_MAP = [
        // Old Processos statuses
        'Ativo'        => 'Em Análise',
        'ativo'        => 'Em Análise',
        'Suspenso'     => 'Aguard. Judiciário',
        'suspenso'     => 'Aguard. Judiciário',
        'Arquivado'    => 'Encerrado',
        'arquivado'    => 'Encerrado',

        // Old Casos statuses
        'aberto'       => 'Novo Caso',
        'em_andamento' => 'Em Análise',
        'encerrado'    => 'Encerrado',
    ];

    public function up(): void
    {
        foreach (self::STATUS_MAP as $old => $new) {
            // Normalize processos
            DB::table('processos')
                ->where('status', $old)
                ->update(['status' => $new]);

            // Normalize law_casos
            DB::table('law_casos')
                ->where('status', $old)
                ->update(['status' => $new]);
        }
    }

    public function down(): void
    {
        // One-way migration — reversal not provided.
        // To rollback, restore from backup or re-assign manually.
    }
};
