<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArmaMotivoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editar-arma-motivo') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('arma_motivos', 'nombre')->ignore($this->route('arma_motivo')),
            ],
            'dias'          => 'required|integer|min:0|max:365',
            'tipo_asignado' => 'required|in:RETENCIÓN,REGULACIÓN,RESGUARDO',
            'activo'        => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required'        => 'El nombre del motivo es obligatorio.',
            'nombre.max'             => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique'          => 'Ya existe un motivo con ese nombre.',
            'dias.required'          => 'Los días son obligatorios.',
            'dias.integer'           => 'Los días deben ser un número entero.',
            'dias.min'               => 'Los días no pueden ser negativos.',
            'dias.max'               => 'Los días no pueden superar los 365.',
            'tipo_asignado.required' => 'El tipo asignado es obligatorio.',
            'tipo_asignado.in'       => 'El tipo asignado debe ser RETENCIÓN, REGULACIÓN o RESGUARDO.',
        ];
    }
}
