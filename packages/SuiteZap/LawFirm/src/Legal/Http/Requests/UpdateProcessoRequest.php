<?php

namespace SuiteZap\LawFirm\Legal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use SuiteZap\LawFirm\Legal\Rules\ValidarCNJ;
use SuiteZap\LawFirm\Legal\Rules\ValidarCpfCnpj;

class UpdateProcessoRequest extends FormRequest
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
        // Pega o ID da rota via request (supondo rota edit/update = {id})
        $processoId = $this->route('id') ?? $this->route('processo');

        return [
            'titulo' => 'required|string|max:255',
            'numero_cnj' => ['nullable', 'string', 'unique:processos,numero_cnj,' . $processoId, new ValidarCNJ],
            'status' => 'required|string|max:255',
            'person_id' => 'nullable|exists:persons,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'lead_id' => 'nullable|exists:leads,id',
            'caso_id' => 'nullable|integer|exists:law_casos,id',
            'tribunal' => 'nullable|string|max:255',
            'comarca' => 'nullable|string|max:255',
            'vara' => 'nullable|string|max:255',
            'protocolo_distribuicao' => 'nullable|string|max:255',
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
            'data_audiencia' => [
                'nullable',
                function ($attribute, $value, $fail) use ($processoId) {
                    if (! $value) {
                        return;
                    }

                    // Tenta parsear em múltiplos formatos que o picker de datetime pode enviar
                    $parsed = null;
                    $formats = ['Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d H:i:s', 'd-m-Y H:i', 'd/m/Y H:i', 'Y-m-d'];
                    foreach ($formats as $fmt) {
                        try {
                            $candidate = \Carbon\Carbon::createFromFormat($fmt, $value);
                            if ($candidate && $candidate->format($fmt) === $value) {
                                $parsed = $candidate;
                                break;
                            }
                        } catch (\Exception $e) {}
                    }

                    // Fallback: deixa o Carbon tentar interpretar automaticamente
                    if (! $parsed) {
                        try {
                            $parsed = \Carbon\Carbon::parse($value);
                        } catch (\Exception $e) {
                            $fail('Formato de data inválido para Data da Audiência.');
                            return;
                        }
                    }

                    // Se o processo já tem uma data de audiência salva e é a mesma, deixa passar
                    $processo = \SuiteZap\LawFirm\Legal\Models\Processo::find($processoId);
                    if ($processo && $processo->data_audiencia) {
                        try {
                            $existingDay = \Carbon\Carbon::parse($processo->data_audiencia)->format('Y-m-d H:i');
                            $incomingDay = $parsed->format('Y-m-d H:i');
                            if ($existingDay === $incomingDay) {
                                return; // mesma data — permite salvar sem verificar passado
                            }
                        } catch (\Exception $e) {}
                    }

                    // Só bloqueia se for uma data genuinamente NOVA e no passado
                    if ($parsed->startOfDay()->lt(\Carbon\Carbon::today())) {
                        $fail('A Data da Audiência não pode ser anterior a hoje, a menos que já esteja salva.');
                    }
                }
            ],
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

            'envolvidos_escavador'         => 'nullable|string',

            // PRAZOS ARRAY
            'prazos' => 'nullable|array',
            'prazos.*.id' => 'nullable|integer',
            'prazos.*.titulo' => 'required_unless:prazos.*.should_delete,1|nullable|string|max:255',
            'prazos.*.tipo' => 'nullable|string|in:prazo,tarefa',
            'prazos.*.data_vencimento' => 'required_unless:prazos.*.should_delete,1|nullable|date',
            'prazos.*.status' => 'required_unless:prazos.*.should_delete,1|nullable|in:pendente,concluido',
            'prazos.*.descricao' => 'nullable|string',
            'prazos.*.should_delete' => 'nullable',

            // NOTAS ARRAY
            'notas' => 'nullable|array',
            'notas.*.id' => 'nullable|integer',
            'notas.*.nota' => 'required_unless:notas.*.should_delete,1|nullable|string',
            'notas.*.should_delete' => 'nullable',

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

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        \Log::warning('UpdateProcessoRequest Validation Failed', [
            'errors' => $validator->errors()->toArray(),
            'payload' => $this->all()
        ]);

        parent::failedValidation($validator);
    }
}
