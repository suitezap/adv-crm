<?php

namespace SuiteZap\LawFirm\Legal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use SuiteZap\LawFirm\Legal\Rules\ValidarCNJ;
use SuiteZap\LawFirm\Legal\Rules\ValidarCpfCnpj;

class StoreProcessoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'titulo' => 'required|string|max:255',
            'numero_cnj' => ['nullable', 'string', 'unique:processos,numero_cnj', new ValidarCNJ],
            'protocolo_distribuicao' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'person_id' => 'required|exists:persons,id',
            'lead_id' => 'nullable|exists:leads,id',
            'tribunal' => 'nullable|string|max:255',
            'comarca' => 'nullable|string|max:255',
            'vara' => 'nullable|string|max:255',
            'juiz_atual' => 'nullable|string|max:255',
            'link_acesso' => 'nullable|string|max:500',
            'fase_processual' => 'nullable|string|max:255',
            'parte_contraria' => 'nullable|string|max:255',
            'opposing_party_name' => 'nullable|string|max:255',
            'opposing_party_type' => 'nullable|in:PF,PJ',
            'opposing_party_document' => [
                'nullable',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    $type = $this->input('opposing_party_type');
                    if ($type === 'PF') {
                        $rule = new \SuiteZap\LawFirm\Legal\Rules\Cpf;
                        if (!$rule->passes($attribute, $value)) {
                            $fail('O CPF da parte contrária é inválido.');
                        }
                    } elseif ($type === 'PJ') {
                        $rule = new \SuiteZap\LawFirm\Legal\Rules\Cnpj;
                        if (!$rule->passes($attribute, $value)) {
                            $fail('O CNPJ da parte contrária é inválido.');
                        }
                    }
                }
            ],
            'advogado_parte_contraria' => 'nullable|string|max:255',
            'area_direito' => 'nullable|string|max:255',
            'probabilidade_exito' => 'nullable|string|max:255',
            'data_distribuicao' => 'nullable|date',
            'data_audiencia' => 'nullable|date',
            'valor_causa' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'tipo_parte' => 'nullable|in:autor,reu',
            'tipo_pessoa' => 'nullable|in:Física,Jurídica',
            'cpf_cnpj' => ['nullable', 'string', 'max:20', new ValidarCpfCnpj],
            'advogado_oab' => 'nullable|string|max:20',
            'whatsapp_advogado_contrario' => ['nullable', 'string', 'max:20', 'regex:/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/'],
            'email_advogado_contrario' => 'nullable|email:rfc,dns|max:255',
            'subarea_direito'              => 'nullable|string|max:255',
            'user_id'                      => 'nullable|exists:users,id',
            'whatsapp_responsavel'         => 'nullable|string|max:50',

            // PRAZOS ARRAY (Permitir campos essenciais para Sync não serem capados no request->validated())
            'prazos' => 'nullable|array',
            'prazos.*.id' => 'nullable|integer',
            'prazos.*.titulo' => 'required_unless:prazos.*.should_delete,1|nullable|string|max:255',
            'prazos.*.data_vencimento' => 'required_unless:prazos.*.should_delete,1|nullable|date',
            'prazos.*.status' => 'required_unless:prazos.*.should_delete,1|nullable|in:pendente,concluido',
            'prazos.*.descricao' => 'nullable|string',
            'prazos.*.should_delete' => 'nullable|boolean|integer',

            // NOTAS ARRAY
            'notas' => 'nullable|array',
            'notas.*.id' => 'nullable|integer',
            'notas.*.nota' => 'required_unless:notas.*.should_delete,1|nullable|string',
            'notas.*.should_delete' => 'nullable|boolean|integer',

            'anexos.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,txt,csv,xls,xlsx,rtf,log,md,xml,odt,ods|max:20480',
        ];
    }

    /**
     * Custom error messages
     */
    public function messages()
    {
        return [
            'whatsapp_advogado_contrario.regex' => 'O formato do WhatsApp é inválido. Use: (99) 99999-9999.',
            'prazos.*.titulo.required' => 'O título do prazo é obrigatório.',
            'prazos.*.data_vencimento.required' => 'A data de vencimento do prazo é obrigatória.',
            'anexos.*.mimes' => 'Tipo de arquivo não permitido. Aceitos: PDF, Imagens, Office, Texto (txt, log, md, csv).',
            'anexos.*.max' => 'O tamanho máximo do arquivo é 20MB.',
        ];
    }
}
