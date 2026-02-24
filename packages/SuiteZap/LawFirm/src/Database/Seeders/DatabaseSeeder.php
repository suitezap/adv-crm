<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Chame aqui todos os seeders do pacote em ordem lógica

        // 1. Seeders de Atributos
        $this->call(AttributeSeeder::class);

        // 2. Seeders de Configuração SaaS
        $this->call(SaasConfigSeeder::class);

        // 3. Seeders de Checklists
        $this->call(ChecklistTemplateSeeder::class);


        // 4. Seeders de IA / Assistants
        $this->call(AssistantSeeder::class);

        // 5. Modelos Específicos de IA Assistants
        $this->call(RoteirizadorAudienciaTemplateSeeder::class);
        $this->call(TransformadorRelatoTemplateSeeder::class);
        $this->call(DecodificadorCnisTemplateSeeder::class);
        $this->call(GeradorQuesitosMedicosTemplateSeeder::class);
        $this->call(AuditorPppTemplateSeeder::class);
        $this->call(FiltroDramaTemplateSeeder::class);
        $this->call(ArquitetoConvivenciaTemplateSeeder::class);
        $this->call(AnalistaSubrogacaoTemplateSeeder::class);
        $this->call(CacadorClausulasLeoninasTemplateSeeder::class);
        $this->call(DosimetroDanoMoralTemplateSeeder::class);
        $this->call(AdvogadoDiaboTemplateSeeder::class);
    }
}
