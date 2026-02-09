<?php

namespace SuiteZap\LawFirm\Services;

class ChecklistTemplates
{
    /**
     * Retorna a estrutura de passos baseada no tipo de caso.
     */
    public static function getTemplate(string $type): array
    {
        switch ($type) {
            case 'labor_claimant':
                return self::getLaborClaimantTemplate();
            case 'family_divorce':
                return self::getFamilyDivorceTemplate();
            case 'civil_general':
                return self::getCivilGeneralTemplate();
            default:
                return [];
        }
    }

    /**
     * Retorna lista de tipos disponíveis para a UI de seleção.
     */
    public static function getAvailableTypes(): array
    {
        return [
            [
                'key' => 'labor_claimant',
                'label' => 'Trabalhista',
                'icon' => '👷',
                'description' => 'Reclamações trabalhistas, verbas rescisórias, horas extras, etc.'
            ],
            [
                'key' => 'family_divorce',
                'label' => 'Família & Sucessões',
                'icon' => '💍',
                'description' => 'Divórcios, inventários, guarda, alimentos, etc.'
            ],
            [
                'key' => 'civil_general',
                'label' => 'Cível Geral',
                'icon' => '⚖️',
                'description' => 'Cobranças, contratos, indenizações, etc.'
            ]
        ];
    }

    private static function getLaborClaimantTemplate(): array
    {
        return [
            [
                "id" => 1,
                "title" => "Confirmações Iniciais",
                "shortTitle" => "Início",
                "description" => "Valide os dados fundamentais para evitar nulidades.",
                "fields" => [
                    ["key" => "contrato_hon", "label" => "Contrato de honorários aceito", "type" => "checkbox"],
                    ["key" => "procuracao", "label" => "Procuração assinada", "type" => "checkbox"],
                    ["key" => "docs_pessoais", "label" => "Documentos pessoais anexados", "type" => "checkbox"],
                    ["key" => "dados_empregador", "label" => "Dados do empregador confirmados", "type" => "checkbox"],
                    ["key" => "tipo_contrato", "label" => "Tipo de contrato identificado", "type" => "checkbox"]
                ]
            ],
            [
                "id" => 2,
                "title" => "Delimitação da Tese",
                "shortTitle" => "Tese",
                "description" => "Defina o escopo da ação.",
                "fields" => [
                    ["key" => "teses_principais", "label" => "Teses principais definidas", "type" => "checkbox"],
                    ["key" => "vinculo", "label" => "Pedido de vínculo", "type" => "checkbox"],
                    ["key" => "verbas_resc", "label" => "Verbas rescisórias", "type" => "checkbox"],
                    ["key" => "horas_extras", "label" => "Horas extras", "type" => "checkbox"],
                    ["key" => "dano_moral", "label" => "Indenização (Moral/Material)", "type" => "checkbox"]
                ]
            ],
            [
                "id" => 3,
                "title" => "Prescrição e Competência",
                "shortTitle" => "Prazos",
                "description" => "Análise crítica de prazos fatais.",
                "fields" => [
                    ["key" => "presc_bienal", "label" => "Prescrição bienal analisada", "type" => "checkbox"],
                    ["key" => "presc_quinquenal", "label" => "Prescrição quinquenal delimitada", "type" => "checkbox"],
                    ["key" => "competencia", "label" => "Competência territorial confirmada", "type" => "checkbox"],
                    ["key" => "rito", "label" => "Rito definido", "type" => "select", "options" => ["Sumaríssimo", "Ordinário", "Juízo 100% Digital"]]
                ]
            ],
            [
                "id" => 4,
                "title" => "Cálculos e Valores",
                "shortTitle" => "Valores",
                "description" => "Quantificação dos pedidos.",
                "fields" => [
                    ["key" => "valor_causa", "label" => "Valor da causa definido", "type" => "checkbox"],
                    ["key" => "calculos_ok", "label" => "Cálculos/Estimativas verificados", "type" => "checkbox"],
                    ["key" => "notas_calculos", "label" => "Observações", "type" => "textarea"]
                ]
            ],
            [
                "id" => 5,
                "title" => "Estratégia Processual",
                "shortTitle" => "Estratég",
                "description" => "Definição da estratégia jurídica.",
                "fields" => [
                    ["key" => "tipo_acao", "label" => "Tipo de Ação", "type" => "select", "options" => ["Reclamação Trabalhista", "Mandado de Segurança", "Execução", "Ação Rescisória"]],
                    ["key" => "testemunhas", "label" => "Testemunhas arroladas", "type" => "checkbox"],
                    ["key" => "descricao_estrategia", "label" => "Detalhes da estratégia", "type" => "textarea"]
                ]
            ],
            [
                "id" => 6,
                "title" => "Petição Inicial",
                "shortTitle" => "Petição",
                "description" => "Elaboração e revisão da peça.",
                "fields" => [
                    ["key" => "peticao_elaborada", "label" => "Minuta pronta", "type" => "checkbox"],
                    ["key" => "peticao_revisada", "label" => "Revisão concluída", "type" => "checkbox"],
                    ["key" => "docs_anexados", "label" => "Documentos anexados à inicial", "type" => "checkbox"]
                ]
            ],
            [
                "id" => 7,
                "title" => "Protocolo",
                "shortTitle" => "Protoc.",
                "description" => "Distribuição/protocolo da ação.",
                "fields" => [
                    ["key" => "peticao_protocolada", "label" => "Protocolado no PJe/e-SAJ", "type" => "checkbox"],
                    ["key" => "numero_processo", "label" => "Nº do Processo", "type" => "text"],
                    ["key" => "vara", "label" => "Vara/Juízo", "type" => "text"]
                ]
            ],
            [
                "id" => 8,
                "title" => "Conferência Final",
                "shortTitle" => "Fim",
                "description" => "A última barreira antes do encerramento.",
                "fields" => [
                    ["key" => "cliente_informado", "label" => "Cliente notificado do protocolo", "type" => "checkbox"],
                    ["key" => "prazo_inicial", "label" => "Prazo inicial cadastrado na agenda", "type" => "checkbox"],
                    ["key" => "observacoes_finais", "label" => "Observações Finais", "type" => "textarea"]
                ]
            ]
        ];
    }

    private static function getFamilyDivorceTemplate(): array
    {
        return [
            [
                "id" => 1,
                "title" => "Triagem Inicial",
                "shortTitle" => "Triagem",
                "description" => "Definição do tipo de divórcio.",
                "fields" => [
                    ["key" => "certidao_casamento", "label" => "Certidão de Casamento Atualizada (90 dias)", "type" => "checkbox"],
                    ["key" => "tipo_divorcio", "label" => "Modalidade", "type" => "select", "options" => ["Consensual Cartório", "Consensual Judicial", "Litigioso"]],
                    ["key" => "filhos_menores", "label" => "Existem filhos menores?", "type" => "checkbox"],
                    ["key" => "procuracao", "label" => "Procuração assinada", "type" => "checkbox"]
                ]
            ],
            [
                "id" => 2,
                "title" => "Partilha de Bens",
                "shortTitle" => "Partilha",
                "description" => "Levantamento patrimonial.",
                "fields" => [
                    ["key" => "lista_imoveis", "label" => "Matrículas de Imóveis", "type" => "checkbox"],
                    ["key" => "lista_veiculos", "label" => "CRLV de Veículos", "type" => "checkbox"],
                    ["key" => "contas_bancarias", "label" => "Contas bancárias/Investimentos", "type" => "checkbox"],
                    ["key" => "dividas", "label" => "Levantamento de Dívidas", "type" => "checkbox"],
                    ["key" => "notas_partilha", "label" => "Observações", "type" => "textarea"]
                ]
            ],
            [
                "id" => 3,
                "title" => "Conclusão",
                "shortTitle" => "Fim",
                "description" => "Preparação para protocolo/assinatura.",
                "fields" => [
                    ["key" => "minuta_aprovada", "label" => "Minuta aprovada pelo cliente", "type" => "checkbox"],
                    ["key" => "guias_pagas", "label" => "Custas/Emolumentos pagos", "type" => "checkbox"],
                    ["key" => "cliente_notificado", "label" => "Cliente notificado", "type" => "checkbox"]
                ]
            ]
        ];
    }

    private static function getCivilGeneralTemplate(): array
    {
        return [
            [
                "id" => 1,
                "title" => "Análise de Viabilidade",
                "shortTitle" => "Análise",
                "description" => "Triagem Cível Genérica.",
                "fields" => [
                    ["key" => "fato_direito", "label" => "Narrativa dos fatos clara", "type" => "checkbox"],
                    ["key" => "provas_iniciais", "label" => "Provas documentais mínimas", "type" => "checkbox"],
                    ["key" => "prescricao", "label" => "Prescrição analisada", "type" => "checkbox"],
                    ["key" => "competencia", "label" => "Competência definida", "type" => "checkbox"]
                ]
            ],
            [
                "id" => 2,
                "title" => "Petição e Protocolo",
                "shortTitle" => "Petição",
                "description" => "Elaboração e distribuição.",
                "fields" => [
                    ["key" => "peticao_elaborada", "label" => "Petição elaborada", "type" => "checkbox"],
                    ["key" => "peticao_revisada", "label" => "Revisão concluída", "type" => "checkbox"],
                    ["key" => "protocolada", "label" => "Ação protocolada", "type" => "checkbox"],
                    ["key" => "numero_processo", "label" => "Nº do Processo", "type" => "text"]
                ]
            ],
            [
                "id" => 3,
                "title" => "Conclusão",
                "shortTitle" => "Fim",
                "description" => "Finalização do checklist.",
                "fields" => [
                    ["key" => "cliente_notificado", "label" => "Cliente notificado", "type" => "checkbox"],
                    ["key" => "observacoes", "label" => "Observações", "type" => "textarea"]
                ]
            ]
        ];
    }
}
