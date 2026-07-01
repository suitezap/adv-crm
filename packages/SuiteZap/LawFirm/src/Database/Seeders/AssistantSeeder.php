<?php

namespace SuiteZap\LawFirm\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssistantSeeder extends Seeder
{
    public function run()
    {
        // Limpar tabela antes de inserir para evitar duplicidade em testes
        DB::table('lawfirm_assistant_templates')->delete();

        DB::table('lawfirm_assistant_templates')->insert([
            [
                'title'       => 'Análise de Risco Contratual',
                'slug'        => 'analise-risco-contrato',
                'category'    => 'contratos',
                'description' => 'Gera um prompt especialista para analisar cláusulas abusivas.',
                'form_schema' => json_encode([
                    [
                        'label'       => 'Tipo de Contrato',
                        'name'        => 'tipo_contrato',
                        'type'        => 'text',
                        'placeholder' => 'Ex: Prestação de Serviços',
                    ],
                    [
                        'label' => 'Valor Envolvido',
                        'name'  => 'valor',
                        'type'  => 'text',
                    ],
                    [
                        'label' => 'Dúvidas Específicas',
                        'name'  => 'duvidas',
                        'type'  => 'textarea',
                    ],
                ]),
                'prompt_structure' => 'Atue como Advogado Sênior. Analise o contrato de {{tipo_contrato}} no valor de {{valor}}. O cliente tem as seguintes dúvidas: {{duvidas}}. Liste os riscos.',
                'n8n_webhook_url'  => null,
                'token_cost'       => 5,
                'is_active'        => 1,
                'created_at'       => Carbon::now(),
                'updated_at'       => Carbon::now(),
            ],
        ]);
    }
}
