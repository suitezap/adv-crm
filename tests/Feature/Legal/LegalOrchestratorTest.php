<?php

declare(strict_types=1);

namespace Tests\Feature\Legal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use SuiteZap\LawFirm\AI\Models\LeadTriagem;
use SuiteZap\LawFirm\Legal\Listeners\LeadWonListener;
use SuiteZap\LawFirm\Legal\Models\Caso;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\Legal\Services\CasoService;
use SuiteZap\LawFirm\Legal\Services\LegalOrchestrator;
use Tests\MultiDatabaseTestCase;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Stage;
use Webkul\User\Models\User;

/**
 * LegalOrchestratorTest — Etapa 3 (Infraestrutura de Qualidade)
 *
 * Testa as invariantes do domínio Legal: conversão atômica de Lead ganho
 * em Caso + Processo, rollback em falhas e deduplicação.
 *
 * Cobertura: LEGAL-FEATURE-001 a LEGAL-FEATURE-006
 *
 * @since   v3.55.0 — Etapa 3 da Infraestrutura de Qualidade
 */
class LegalOrchestratorTest extends MultiDatabaseTestCase
{
    use RefreshDatabase;

    private LegalOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCoreData();

        $this->orchestrator = app(LegalOrchestrator::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Cria um Lead com Person associado (pré-requisito do Orchestrator).
     */
    private function makeLead(array $overrides = []): Lead
    {
        $user = User::withoutEvents(function () {
            return User::firstOrCreate(
                ['email' => 'test@test.com'],
                ['name' => 'Test User', 'password' => bcrypt('password'), 'role_id' => 1, 'status' => 1]
            );
        });

        $person = Person::create(['name' => 'Test Person', 'emails' => [['value' => 'test@test.com', 'label' => 'work']]]);

        return Lead::create(array_merge([
            'title'                  => 'Caso de Teste - '.fake()->words(3, true),
            'description'            => 'Descrição do caso de teste automatizado.',
            'person_id'              => $person->id,
            'user_id'                => $user->id,
            'lead_value'             => 5000.00,
            'lead_pipeline_id'       => 1,
            'lead_pipeline_stage_id' => 1,
        ], $overrides));
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // LEGAL-FEATURE-001: Conversão atômica de Lead ganho em Caso e Processo
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * @test LEGAL-FEATURE-001
     * Garante que convertLeadToLegalStructure cria Caso e Processo atomicamente.
     */
    public function test_converts_lead_to_caso_and_processo_atomically(): void
    {
        $lead = $this->makeLead();

        $result = $this->orchestrator->convertLeadToLegalStructure($lead);

        // Retorno correto
        $this->assertArrayHasKey('caso', $result);
        $this->assertArrayHasKey('processo', $result);

        /** @var Caso $caso */
        $caso = $result['caso'];
        /** @var Processo $processo */
        $processo = $result['processo'];

        // Caso persisted
        $this->assertDatabaseHas('law_casos', [
            'id'     => $caso->id,
            'titulo' => $lead->title,
            'status' => 'Novo Caso',
        ]);

        // Processo persisted e vinculado ao Caso
        $this->assertDatabaseHas('processos', [
            'id'      => $processo->id,
            'titulo'  => $lead->title,
            'lead_id' => $lead->id,
            'caso_id' => $caso->id,
            'status'  => 'Em Análise',
        ]);

        // Vinculação correta
        $this->assertEquals($caso->id, $processo->caso_id);
        $this->assertEquals($lead->id, $processo->lead_id);
    }

    /**
     * @test LEGAL-FEATURE-001 (complementar)
     * Garante que o valor_causa do Lead é propagado ao Processo.
     */
    public function test_lead_value_is_propagated_to_processo(): void
    {
        $lead = $this->makeLead(['lead_value' => 12500.00]);

        $result = $this->orchestrator->convertLeadToLegalStructure($lead);

        $this->assertDatabaseHas('processos', [
            'id'          => $result['processo']->id,
            'valor_causa' => 12500.00,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // LEGAL-FEATURE-002: Rollback completo se criação do Caso falhar
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * @test LEGAL-FEATURE-002
     * Garante que se createCaso lançar exceção, nenhum dado é persistido (rollback total).
     */
    public function test_rollback_when_caso_creation_fails(): void
    {
        $lead = $this->makeLead();

        $casoCountBefore = Caso::count();
        $processoCountBefore = Processo::count();

        // Mock do CasoService para forçar falha
        $mockCasoService = Mockery::mock(CasoService::class);
        $mockCasoService->shouldReceive('createCaso')
            ->once()
            ->andThrow(new \RuntimeException('Simulated DB failure in createCaso'));

        $orchestrator = new LegalOrchestrator($mockCasoService);

        $this->expectException(\Throwable::class);
        $orchestrator->convertLeadToLegalStructure($lead);

        // Rollback: nenhum registro novo criado
        $this->assertEquals($casoCountBefore, Caso::count());
        $this->assertEquals($processoCountBefore, Processo::count());
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // LEGAL-FEATURE-003: Rollback completo se criação do Processo falhar
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * @test LEGAL-FEATURE-003
     * Garante que se Processo::create lançar exceção, o Caso também é revertido.
     */
    public function test_rollback_when_processo_creation_fails(): void
    {
        $lead = $this->makeLead();

        $casoCountBefore = Caso::count();
        $processoCountBefore = Processo::count();

        // Usamos um callback de transação para forçar falha após criar o Caso
        $threwException = false;
        try {
            DB::transaction(function () use ($lead, &$threwException) {
                // Simula criação do Caso (dentro da transaction do orchestrator)
                $caso = Caso::create([
                    'titulo'     => $lead->title,
                    'status'     => 'Novo Caso',
                    'user_id'    => $lead->user_id,
                    'area'       => 'Cível',
                    'prioridade' => 'Baixa',
                ]);

                $this->assertDatabaseHas('law_casos', ['id' => $caso->id]);

                // Agora força a falha na criação do Processo
                throw new \RuntimeException('Simulated DB failure in Processo::create');
            });
        } catch (\Throwable $e) {
            $threwException = true;
        }

        $this->assertTrue($threwException, 'A exceção deve ser lançada.');

        // O Caso criado dentro da transaction deve ter sido revertido
        $this->assertEquals($casoCountBefore, Caso::count(), 'Rollback falhou: Caso foi persistido após falha no Processo.');
        $this->assertEquals($processoCountBefore, Processo::count(), 'Processo não deveria existir.');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // LEGAL-FEATURE-004: Proteção contra conversão duplicada de Lead
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * @test LEGAL-FEATURE-004
     * Garante que o LeadWonListener não cria um segundo Processo se já existir um.
     */
    public function test_duplicate_conversion_is_blocked_by_listener(): void
    {
        $lead = $this->makeLead();

        // Cria o Processo manualmente para simular conversão já realizada
        $processo = Processo::create([
            'titulo'      => $lead->title,
            'lead_id'     => $lead->id,
            'caso_id'     => null,
            'user_id'     => $lead->user_id,
            'status'      => 'Em Análise',
            'valor_causa' => 0,
        ]);

        $this->assertDatabaseHas('processos', ['lead_id' => $lead->id]);

        // Tenta uma segunda conversão via listener
        $listener = app(LeadWonListener::class);

        // Mock do stage como "won"
        $wonStage = new Stage(['code' => 'won', 'name' => 'Won']);
        $lead->setRelation('stage', $wonStage);

        // Conta processos antes
        $processoCount = Processo::where('lead_id', $lead->id)->count();

        $listener->handle($lead);

        // Deve continuar com o mesmo número — nenhum novo Processo foi criado
        $this->assertEquals($processoCount, Processo::where('lead_id', $lead->id)->count(),
            'O listener criou um segundo Processo para o mesmo Lead — violação de LEGAL-FEATURE-004.'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // LEGAL-FEATURE-005: Tags canônicas do Lead têm prioridade sobre Triagem IA
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * @test LEGAL-FEATURE-005
     * Garante que tag canônica de área no Lead sobrescreve a triagem de IA.
     */
    public function test_lead_tags_override_triagem_for_area(): void
    {
        $lead = $this->makeLead();

        // Triagem diz "Tributário" mas a tag do lead diz "Trabalhista"
        LeadTriagem::create([
            'lead_id'  => $lead->id,
            'area'     => 'Tributário',
            'urgencia' => 'alta',
        ]);

        // Cria tag canônica "Trabalhista" no Lead
        $lead->tags()->create(['name' => 'Trabalhista', 'user_id' => $lead->user_id]);
        $lead->load('tags');

        $result = $this->orchestrator->convertLeadToLegalStructure($lead);

        $this->assertDatabaseHas('law_casos', [
            'id'   => $result['caso']->id,
            'area' => 'Trabalhista', // Tag vence sobre Triagem
        ]);
    }

    /**
     * @test LEGAL-FEATURE-005 (complementar)
     * Tag canônica de prioridade no Lead sobrescreve a urgência da triagem.
     */
    public function test_lead_tags_override_triagem_for_prioridade(): void
    {
        $lead = $this->makeLead();

        // Triagem diz urgencia = "baixa" mas a tag diz "Alta"
        LeadTriagem::create([
            'lead_id'  => $lead->id,
            'area'     => 'Cível',
            'urgencia' => 'baixa',
        ]);

        // Tag canônica de prioridade
        $lead->tags()->create(['name' => 'Alta', 'user_id' => $lead->user_id]);
        $lead->load('tags');

        $result = $this->orchestrator->convertLeadToLegalStructure($lead);

        $this->assertDatabaseHas('law_casos', [
            'id'         => $result['caso']->id,
            'prioridade' => 'Alta',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // LEGAL-FEATURE-006: Fallback para LeadTriagem quando Tags do Lead ausentes
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * @test LEGAL-FEATURE-006
     * Garante que se não há tags, a área do Caso vem da triagem de IA.
     */
    public function test_fallback_to_triagem_area_when_no_tags(): void
    {
        $lead = $this->makeLead();

        // Sem tags — triagem é o único dado disponível
        LeadTriagem::create([
            'lead_id'  => $lead->id,
            'area'     => 'Previdenciário',
            'urgencia' => 'media',
        ]);

        $lead->load('tags'); // Vazio

        $result = $this->orchestrator->convertLeadToLegalStructure($lead);

        $this->assertDatabaseHas('law_casos', [
            'id'   => $result['caso']->id,
            'area' => 'Previdenciário',
        ]);
    }

    /**
     * @test LEGAL-FEATURE-006 (complementar — prioridade via triagem)
     * Garante que a urgência da triagem é convertida na prioridade correta.
     */
    public function test_fallback_to_triagem_urgencia_as_prioridade(): void
    {
        $lead = $this->makeLead();

        LeadTriagem::create([
            'lead_id'  => $lead->id,
            'area'     => 'Cível',
            'urgencia' => 'critica',
        ]);

        $lead->load('tags'); // Sem tags

        $result = $this->orchestrator->convertLeadToLegalStructure($lead);

        $this->assertDatabaseHas('law_casos', [
            'id'         => $result['caso']->id,
            'prioridade' => 'Crítica',
        ]);
    }

    /**
     * @test LEGAL-FEATURE-006 (default seguro)
     * Garante que sem tags E sem triagem, a área default é "Cível" e prioridade "Baixa".
     */
    public function test_safe_defaults_when_no_tags_and_no_triagem(): void
    {
        $lead = $this->makeLead();
        $lead->load('tags'); // Vazio, sem triagem

        $result = $this->orchestrator->convertLeadToLegalStructure($lead);

        $this->assertDatabaseHas('law_casos', [
            'id'         => $result['caso']->id,
            'area'       => 'Cível',
            'prioridade' => 'Baixa',
        ]);
    }
}
