<?php

namespace SuiteZap\LawFirm\Legal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCasoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'titulo'          => 'required|string|max:255',
            'area'            => 'nullable|string|max:100',
            'status'          => 'nullable|string|in:aberto,em_andamento,encerrado,arquivado',
            'prioridade'      => 'nullable|string|in:baixa,media,alta,critica',
            'descricao'       => 'nullable|string',
            'user_id'         => 'nullable|integer|exists:users,id',
            'person_id'       => 'nullable|integer|exists:persons,id',
            'organization_id' => 'nullable|integer|exists:organizations,id',
        ];
    }
}
