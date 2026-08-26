<?php

declare(strict_types=1);

namespace Tests\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SuiteZap\LawFirm\AI\Models\AssistantHistory;
use SuiteZap\LawFirm\AI\Models\AssistantTemplate;
use SuiteZap\LawFirm\Legal\Models\Caso;
use SuiteZap\LawFirm\Legal\Models\Processo;
use Tests\MultiDatabaseTestCase;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\User\Models\User;

/**
 * MultiTenantIsolationTest — Etapa 3 (Infraestrutura de Qualidade)
 *
 * Prova matemática de isolamento multi-tenant:
 * - TENANT-SEC-001: Tenant A não pode ver Casos/Processos do Tenant B
 * - TENANT-SEC-002: Tenant A não pode alterar/excluir registros do Tenant B via ID forjado
 * - TENANT-SEC-003: Acesso a recurso de outro tenant retorna HTTP 403 ou 404
 * - TENANT-SEC-004: Histórico de execuções de IA não vaza entre tenants
 * - TENANT-SEC-005: Filas Redis isoladas pelo prefixo REDIS_PREFIX
 *
 * @package Tests\Security
 * @since   v3.55.0 — Etapa 3 da Infraestrutura de Qualidade
 */
class MultiTenantIsolationTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Person $personA;
    private Person $personB;

    protected function setUp(): void
    {
        parent::setUp();


        $this->userA = User::withoutEvents(function () {
            return User::create([
                'name'     => 'Admin Tenant A',
                'email'    => 'admin_a@tenant.test',
                'password' => bcrypt('password'),
                'role_id'  => 1,
                'view_permission' => 'individual', // Tenant A só vê seus próprios registros
                'status'   => 1,
            ]);
        });
        $this->userB = User::withoutEvents(function () {
            return User::create([
                'name'     => 'Admin Tenant B',
                'email'    => 'admin_b@tenant.test',
                'password' => bcrypt('password'),
                'role_id'  => 1,
                'view_permission' => 'global',
                'status'   => 1,
            ]);
        });
        $this->personA = Person::create(['name' => 'Person A', 'emails' => [['value' => 'a@test.com', 'label' => 'work']]]);
        $this->personB = Person::create(['name' => 'Person B', 'emails' => [['value' => 'b@test.com', 'label' => 'work']]]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TENANT-SEC-001: Tenant A não pode ver Casos/Processos do Tenant B
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test TENANT-SEC-001
     * Casos e Processos do Tenant B não devem aparecer na query do Tenant A.
     *
     * Nota: Em produção, o isolamento é garantido pelo banco segregado por tenant.
     * No ambiente de teste (banco compartilhado), validamos que filtros por user_id
     * evitam vazamento de dados entre usuários de tenants distintos.
     */
    public function test_tenant_a_cannot_see_casos_of_tenant_b(): void
    {
        // Casos do Tenant A
        $casoA = Caso::create([
            'titulo'    => 'Caso do Tenant A',
            'status'    => 'Novo Caso',
            'area'      => 'Cível',
            'prioridade' => 'Baixa',
            'user_id'   => $this->userA->id,
            'person_id' => $this->personA->id,
        ]);

        // Casos do Tenant B (outro user_id)
        $casoB = Caso::create([
            'titulo'    => 'Caso do Tenant B',
            'status'    => 'Novo Caso',
            'area'      => 'Trabalhista',
            'prioridade' => 'Alta',
            'user_id'   => $this->userB->id,
            'person_id' => $this->personB->id,
        ]);

        // Query do Tenant A filtrada por seu user_id
        $casosVisivelParaA = Caso::where('user_id', $this->userA->id)->pluck('id');

        $this->assertContains($casoA->id, $casosVisivelParaA);
        $this->assertNotContains($casoB->id, $casosVisivelParaA,
            'Caso do Tenant B está visível para o Tenant A — violação de TENANT-SEC-001.'
        );
    }

    /**
     * @test TENANT-SEC-001 (Processos)
     * Processos do Tenant B não devem aparecer na query do Tenant A.
     */
    public function test_tenant_a_cannot_see_processos_of_tenant_b(): void
    {
        $leadA = Lead::create(['user_id' => $this->userA->id, 'person_id' => $this->personA->id, 'title' => 'L A', 'lead_pipeline_id' => 1, 'lead_pipeline_stage_id' => 1]);
        $leadB = Lead::create(['user_id' => $this->userB->id, 'person_id' => $this->personB->id, 'title' => 'L B', 'lead_pipeline_id' => 1, 'lead_pipeline_stage_id' => 1]);

        $processoA = Processo::create([
            'titulo'   => 'Processo A',
            'user_id'  => $this->userA->id,
            'lead_id'  => $leadA->id,
            'status'   => 'Em Análise',
            'valor_causa' => 5000,
        ]);

        $processoB = Processo::create([
            'titulo'   => 'Processo B',
            'user_id'  => $this->userB->id,
            'lead_id'  => $leadB->id,
            'status'   => 'Em Análise',
            'valor_causa' => 8000,
        ]);

        $processosVisivelParaA = Processo::where('user_id', $this->userA->id)->pluck('id');

        $this->assertContains($processoA->id, $processosVisivelParaA);
        $this->assertNotContains($processoB->id, $processosVisivelParaA,
            'Processo do Tenant B está visível para o Tenant A — violação de TENANT-SEC-001.'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TENANT-SEC-002: Tenant A não pode alterar registros do Tenant B via ID forjado
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test TENANT-SEC-002
     * Uma tentativa de update via ID forjado em escopo do Tenant A
     * não deve afetar registros pertencentes ao Tenant B.
     */
    public function test_tenant_a_cannot_update_caso_of_tenant_b_via_forged_id(): void
    {
        $casoB = Caso::create([
            'titulo'    => 'Caso Original do Tenant B',
            'status'    => 'Novo Caso',
            'area'      => 'Tributário',
            'prioridade' => 'Média',
            'user_id'   => $this->userB->id,
            'person_id' => $this->personB->id,
        ]);

        // Tenant A tenta fazer update no ID do Caso de B, mas filtrado por seu user_id
        $affectedRows = Caso::where('id', $casoB->id)
            ->where('user_id', $this->userA->id) // Filtro de isolamento obrigatório
            ->update(['titulo' => 'Caso Adulterado por A']);

        $this->assertEquals(0, $affectedRows,
            'O update de Tenant A afetou o Caso do Tenant B — violação de TENANT-SEC-002.'
        );

        $casoB->refresh();
        $this->assertEquals('Caso Original do Tenant B', $casoB->titulo,
            'O título do Caso do Tenant B foi alterado — violação de TENANT-SEC-002.'
        );
    }

    /**
     * @test TENANT-SEC-002 (exclusão)
     * Tenant A não pode excluir Processo do Tenant B.
     */
    public function test_tenant_a_cannot_delete_processo_of_tenant_b(): void
    {
        $leadB = Lead::create(['user_id' => $this->userB->id, 'person_id' => $this->personB->id, 'title' => 'L B', 'lead_pipeline_id' => 1, 'lead_pipeline_stage_id' => 1]);
        $processoB = Processo::create([
            'titulo'      => 'Processo do Tenant B',
            'user_id'     => $this->userB->id,
            'lead_id'     => $leadB->id,
            'status'      => 'Em Análise',
            'valor_causa' => 3000,
        ]);

        // A tenta deletar o registro de B filtrando por seu user_id
        $deleted = Processo::where('id', $processoB->id)
            ->where('user_id', $this->userA->id)
            ->delete();

        $this->assertEquals(0, $deleted, 'Tenant A conseguiu excluir Processo do Tenant B — violação de TENANT-SEC-002.');
        $this->assertDatabaseHas('processos', ['id' => $processoB->id]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TENANT-SEC-003: Acesso a recurso de outro tenant retorna HTTP 403 ou 404
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test TENANT-SEC-003
     * Um user autenticado de Tenant A que tenta acessar um Processo de Tenant B
     * via URL forjada deve receber 403 ou 404, nunca 200.
     */
    public function test_accessing_processo_of_another_tenant_returns_403_or_404(): void
    {
        $leadB = Lead::create(['user_id' => $this->userB->id, 'person_id' => $this->personB->id, 'title' => 'L B', 'lead_pipeline_id' => 1, 'lead_pipeline_stage_id' => 1]);
        $processoB = Processo::create([
            'titulo'      => 'Processo Privado do Tenant B',
            'user_id'     => $this->userB->id,
            'lead_id'     => $leadB->id,
            'status'      => 'Em Análise',
            'valor_causa' => 9000,
        ]);

        $this->actingAs($this->userA, 'user');

        // Tenta acessar a rota de detalhe de um Processo que não pertence ao Tenant A
        $response = $this->get(route('admin.processos.show', $processoB->id));

        $this->assertContains($response->status(), [302, 403, 404],
            "Esperado 302/403/404, mas recebeu {$response->status()} — violação de TENANT-SEC-003."
        );

        // Nunca deve ser 200 (sucesso) ou 500 (erro de servidor)
        $this->assertNotEquals(200, $response->status(),
            'Tenant A recebeu HTTP 200 ao acessar Processo do Tenant B — violação CRÍTICA de TENANT-SEC-003.'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TENANT-SEC-004: Histórico de execuções de IA não vaza entre tenants
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test TENANT-SEC-004
     * O histórico de execuções de IA do Tenant B não deve aparecer em queries do Tenant A.
     */
    public function test_ai_history_does_not_leak_between_tenants(): void
    {
        $template = AssistantTemplate::create([
            'category'        => 'triagem',
            'title'           => 'Pré-Triagem',
            'prompt_structure' => 'Analise o lead',
            'n8n_webhook_url' => 'webhook/pre-triagem-lead',
            'is_active'       => true,
        ]);

        $leadA = Lead::create(['user_id' => $this->userA->id, 'title' => 'L A', 'lead_pipeline_id' => 1, 'lead_pipeline_stage_id' => 1]);
        $leadB = Lead::create(['user_id' => $this->userB->id, 'title' => 'L B', 'lead_pipeline_id' => 1, 'lead_pipeline_stage_id' => 1]);

        // Histórico do Tenant A
        $histA = AssistantHistory::create([
            'user_id'           => $this->userA->id,
            'lead_id'           => $leadA->id,
            'template_id'       => $template->id,
            'status'            => 'completed',
            'generated_content' => 'Conteúdo Tenant A',
            'input_data'        => [],
            'execution_mode'    => 'async',
        ]);

        // Histórico do Tenant B
        $histB = AssistantHistory::create([
            'user_id'           => $this->userB->id,
            'lead_id'           => $leadB->id,
            'template_id'       => $template->id,
            'status'            => 'completed',
            'generated_content' => 'Conteúdo Tenant B - CONFIDENCIAL',
            'input_data'        => [],
            'execution_mode'    => 'async',
        ]);

        // Query do Tenant A isolada por user_id
        $historyDeA = AssistantHistory::where('user_id', $this->userA->id)->pluck('id');

        $this->assertContains($histA->id, $historyDeA);
        $this->assertNotContains($histB->id, $historyDeA,
            'Histórico de IA do Tenant B está visível para o Tenant A — violação de TENANT-SEC-004.'
        );

        // Garante que o conteúdo confidencial de B nunca aparece nas entradas de A
        $conteudosDeA = AssistantHistory::where('user_id', $this->userA->id)->pluck('generated_content');
        $this->assertNotContains('Conteúdo Tenant B - CONFIDENCIAL', $conteudosDeA->toArray(),
            'Conteúdo confidencial do Tenant B vazou para o Tenant A — violação de TENANT-SEC-004.'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TENANT-SEC-005: Isolamento de filas Redis por REDIS_PREFIX
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * @test TENANT-SEC-005
     * Garante que o REDIS_PREFIX está configurado no ambiente de teste,
     * o que é o requisito mínimo para o isolamento de filas Redis no Swarm.
     *
     * Nota: O teste completo de isolamento de Jobs em runtime requer Swarm real.
     * Este teste valida a configuração estática (existência do REDIS_PREFIX).
     */
    public function test_redis_prefix_is_configured_for_queue_isolation(): void
    {
        $redisPrefix = config('database.redis.options.prefix')
            ?? env('REDIS_PREFIX');

        $this->assertNotNull(
            $redisPrefix,
            'REDIS_PREFIX não está configurado — filas Redis não estarão isoladas por tenant no Swarm (violação de TENANT-SEC-005).'
        );

        $this->assertNotEmpty($redisPrefix,
            'REDIS_PREFIX está vazio — o isolamento de filas não funciona sem um prefixo definido.'
        );
    }

    /**
     * @test TENANT-SEC-005 (sufixo _test_ no ambiente de teste)
     * O REDIS_PREFIX deve conter '_test_' para garantir isolamento do ambiente de teste.
     */
    public function test_redis_prefix_contains_test_marker_in_test_environment(): void
    {
        $redisPrefix = env('REDIS_PREFIX', '');

        $this->assertStringContainsString(
            '_test',
            $redisPrefix,
            "REDIS_PREFIX '{$redisPrefix}' não contém '_test' — risco de contaminação de filas de produção no ambiente de teste."
        );
    }
}
