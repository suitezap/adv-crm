<?php

namespace SuiteZap\LawFirm\Legal\Services;

use Carbon\Carbon;
use SuiteZap\LawFirm\Legal\Models\DocumentTemplate;
use SuiteZap\LawFirm\Legal\Models\MothershipDocumentTemplate;
use SuiteZap\LawFirm\Legal\Models\Processo;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;

class DocumentTemplateService
{
    /**
     * Renders a document template by replacing variables with data from the Processo.
     * Accepts both local (DocumentTemplate) and global (MothershipDocumentTemplate) templates —
     * both share the same interface: they have a `conteudo` property and a `titulo` property.
     *
     * @param  DocumentTemplate|MothershipDocumentTemplate  $template
     */
    public function render($template, ?Processo $processo = null): string
    {
        $variables = $this->buildVariablesArray($processo);
        $content = $template->conteudo;

        foreach ($variables as $key => $value) {
            // Support {{variable}} format
            $content = str_replace('{{'.$key.'}}', $value, $content);
            // Support {{ variable }} format
            $content = str_replace('{{ '.$key.' }}', $value, $content);
        }

        return $content;
    }

    /**
     * Builds the key-value array of variables for template interpolation.
     */
    private function buildVariablesArray(?Processo $processo = null): array
    {
        $tenantConfig = MotherShipService::getTenantConfig();

        $variables = [
            // General
            'data_hoje'       => Carbon::now()->format('d/m/Y'),

            // Escritório (Prioriza configurações locais de personalização do Tenant e depois o Mothership)
            'escritorio_nome'        => core()->getConfigData('lawfirm.settings.general.company_name') ?? $tenantConfig['name'] ?? '',
            'escritorio_whatsapp'    => core()->getConfigData('lawfirm.settings.general.contact_whatsapp') ?? $tenantConfig['whatsapp'] ?? $tenantConfig['phone'] ?? '',
            'escritorio_email'       => core()->getConfigData('lawfirm.settings.general.contact_email') ?? $tenantConfig['email'] ?? '',
            'escritorio_cep'         => core()->getConfigData('lawfirm.settings.general.address_cep') ?? $tenantConfig['cep'] ?? '',
            'escritorio_logradouro'  => core()->getConfigData('lawfirm.settings.general.address_street') ?? $tenantConfig['logradouro'] ?? $tenantConfig['address'] ?? '',
            'escritorio_numero'      => core()->getConfigData('lawfirm.settings.general.address_number') ?? $tenantConfig['numero'] ?? '',
            'escritorio_complemento' => core()->getConfigData('lawfirm.settings.general.address_complement') ?? $tenantConfig['complemento'] ?? '',
            'escritorio_bairro'      => core()->getConfigData('lawfirm.settings.general.address_province') ?? $tenantConfig['bairro'] ?? '',
            'escritorio_cidade'      => core()->getConfigData('lawfirm.settings.general.city') ?? $tenantConfig['city'] ?? $tenantConfig['cidade'] ?? '',
            'escritorio_uf'          => core()->getConfigData('lawfirm.settings.general.address_state') ?? $tenantConfig['uf'] ?? $tenantConfig['state'] ?? '',

            // Person (cliente)
            'cliente_nome'         => '',
            'cliente_cpf'          => '',
            'cliente_rg'           => '',
            'cliente_email'        => '',
            'cliente_telefone'     => '',
            'cliente_estado_civil' => '',
            'cliente_nacionalidade'=> '',
            'cliente_profissao'    => '',
            'cliente_cep'          => '',
            'cliente_logradouro'   => '',
            'cliente_numero'       => '',
            'cliente_complemento'  => '',
            'cliente_bairro'       => '',
            'cliente_cidade'       => '',
            'cliente_uf'           => '',

            // Organization
            'empresa_nome' => '',
            'empresa_cnpj' => '',

            // Lawyer (Responsavel)
            'advogado_nome'      => '',
            'advogado_oab'       => $processo->lawyer_oab ?? '',
            'advogado_whatsapp'  => '',

            // Processo details
            'processo_numero_cnj'  => $processo->numero_cnj ?? '',
            'processo_titulo'      => $processo->titulo ?? '',
            'processo_area'        => $processo->area_direito ?? '',
            'processo_tribunal'    => $processo->tribunal ?? '',
            'processo_vara'        => $processo->vara ?? '',
            'processo_comarca'     => $processo->comarca ?? '',
            'processo_valor_causa' => ($processo && $processo->valor_causa)
                                      ? 'R$ '.number_format($processo->valor_causa, 2, ',', '.')
                                      : '',
            'parte_contraria'      => $processo->opposing_party_name ?? '',
        ];

        if ($processo) {
            // Populate Person details if available
            if ($processo->person) {
                $variables['cliente_nome'] = $processo->person->name ?? '';

                // detail is a separate law_person_details record (may be null if never filled)
                $personDetail = \SuiteZap\LawFirm\Legal\Models\LawPersonDetail::where('person_id', $processo->person->id)->first();
                $variables['cliente_cpf'] = $personDetail->cpf ?? '';
                $variables['cliente_rg'] = $personDetail->rg ?? '';
                $variables['cliente_estado_civil'] = $personDetail->estado_civil ?? '';
                $variables['cliente_nacionalidade'] = $personDetail->nacionalidade ?? '';
                $variables['cliente_profissao'] = $personDetail->profissao ?? '';
                $variables['cliente_cep'] = $personDetail->cep ?? '';
                $variables['cliente_logradouro'] = $personDetail->logradouro ?? '';
                $variables['cliente_numero'] = $personDetail->numero ?? '';
                $variables['cliente_complemento'] = $personDetail->complemento ?? '';
                $variables['cliente_bairro'] = $personDetail->bairro ?? '';
                $variables['cliente_cidade'] = $personDetail->cidade ?? '';
                $variables['cliente_uf'] = $personDetail->uf ?? '';

                // emails and contact_numbers are plain PHP arrays on the Krayin Person model
                $firstEmail = collect($processo->person->emails)->first();
                $firstNumber = collect($processo->person->contact_numbers)->first();
                $variables['cliente_email'] = is_array($firstEmail) ? ($firstEmail['value'] ?? '') : ($firstEmail->value ?? '');
                $variables['cliente_telefone'] = is_array($firstNumber) ? ($firstNumber['value'] ?? '') : ($firstNumber->value ?? '');
            }

            // Populate Organization details if available
            if ($processo->organization) {
                $variables['empresa_nome'] = $processo->organization->name ?? '';
                // detail is a separate law_organization_details record (may be null)
                $orgDetail = \SuiteZap\LawFirm\Legal\Models\LawOrganizationDetail::where('organization_id', $processo->organization->id)->first();
                $variables['empresa_cnpj'] = $orgDetail->cnpj ?? '';
            }

            // Populate Responsavel (Lawyer) details if available
            if ($processo->responsavel) {
                $variables['advogado_nome'] = $processo->responsavel->name ?? '';
                $variables['advogado_whatsapp'] = $processo->responsavel->whatsapp ?? '';
            }
        }

        return $variables;
    }
}
