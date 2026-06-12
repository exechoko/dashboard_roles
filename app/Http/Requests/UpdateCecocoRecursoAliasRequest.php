<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCecocoRecursoAliasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'alias_cecoco' => strtoupper(trim((string) $this->input('alias_cecoco'))),
            'activo' => $this->boolean('activo'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $alias = $this->route('cecocoRecursoAlias');

        return [
            'alias_cecoco' => [
                'required',
                'string',
                'max:50',
                Rule::unique('cecoco_recurso_aliases', 'alias_cecoco')->ignore($alias?->id),
            ],
            'recurso_id' => ['nullable', 'required_without:equipo_id', 'exists:recursos,id'],
            'equipo_id' => ['nullable', 'required_without:recurso_id', 'exists:equipos,id'],
            'activo' => ['boolean'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alias_cecoco.required' => 'El alias CECOCO es obligatorio.',
            'alias_cecoco.unique' => 'Ya existe un mapeo cargado para ese alias CECOCO.',
            'recurso_id.required_without' => 'Debe seleccionar un recurso CAR911 o un equipo CAR911.',
            'equipo_id.required_without' => 'Debe seleccionar un recurso CAR911 o un equipo CAR911.',
            'recurso_id.exists' => 'El recurso CAR911 seleccionado no existe.',
            'equipo_id.exists' => 'El equipo CAR911 seleccionado no existe.',
        ];
    }
}
