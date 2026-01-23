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
        $this->call(AdditionalChecklistSeeder::class);

        // 4. Seeders de IA / Assistants
        $this->call(AssistantSeeder::class);
    }
}
