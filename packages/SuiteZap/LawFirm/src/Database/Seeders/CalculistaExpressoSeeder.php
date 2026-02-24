<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalculistaExpressoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conn = DB::connection('mothership');

        $template = [
            'tenant_id' => null,
            'slug' => 'calculista-expresso',
            'category' => 'calculos',
            'title' => 'Calculista Expresso',
            'description' => 'Estimativa aproximada das verbas rescisórias para base de Valor da Causa.',
            'icon' => 'heroicon-calculator',
            'prompt_structure' => 'Com base nos dados abaixo (CLT Brasil), faça uma estimativa aproximada das verbas rescisórias (Aviso Prévio, 13º Proporcional, Férias + 1/3, Multa 40% FGTS). Não precisa ser um cálculo contábil exato, mas uma base para Valor da Causa.' . "\n\n" . 'Data de Admissão: {{data_admissao}}' . "\n" . 'Data de Demissão: {{data_demissao}}' . "\n" . 'Último Salário: {{ultimo_salario}}' . "\n" . 'Motivo da Demissão: {{motivo_demissao}}' . "\n" . 'Extras: {{extras}}',
            'variables' => json_encode([
                ['key' => 'data_admissao', 'label' => 'Data de Admissão', 'type' => 'date'],
                ['key' => 'data_demissao', 'label' => 'Data de Demissão', 'type' => 'date'],
                ['key' => 'ultimo_salario', 'label' => 'Último Salário (R$)', 'type' => 'text', 'placeholder' => 'Ex: 3500,00'],
                [
                    'key' => 'motivo_demissao',
                    'label' => 'Motivo da Demissão',
                    'type' => 'select',
                    'options' => [
                        'Sem justa causa',
                        'Pedido de demissão',
                        'Por justa causa',
                        'Rescisão indireta'
                    ]
                ],
                ['key' => 'extras', 'label' => 'Informações Extras', 'type' => 'textarea', 'placeholder' => 'Ex: Fazia 2 horas extras por dia, Não recebia periculosidade'],
            ]),
            'n8n_webhook_url' => 'lawfirm/calculista-expresso',
            'required_module' => 'lawfirm_process', // Assuming it's related to processes
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $exists = $conn->table('lawfirm_assistant_templates')->where('slug', 'calculista-expresso')->first();

        if ($exists) {
            $conn->table('lawfirm_assistant_templates')->where('slug', 'calculista-expresso')->update($template);
            $this->command->info('Calculista Expresso assistant template updated.');
        } else {
            $conn->table('lawfirm_assistant_templates')->insert($template);
            $this->command->info('Calculista Expresso assistant template added.');
        }
    }
}
