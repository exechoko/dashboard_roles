<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArmaPersonalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar-personal') === true;
    }

    public function rules(): array
    {
        return [
            'jerarquia'      => 'required|string|max:100',
            'numeracion_arma' => 'nullable|string|max:50',
            'arma_tipo_id'   => 'nullable|exists:arma_tipos,id',
            'nro_chaleco'    => 'nullable|string|max:50',
            'cambiar_arma'   => 'nullable|boolean',
            'motivo_cambio'  => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'jerarquia.required'      => 'La jerarquía es obligatoria.',
            'jerarquia.max'           => 'La jerarquía no puede superar los 100 caracteres.',
            'numeracion_arma.max'     => 'La numeración del arma no puede superar los 50 caracteres.',
            'arma_tipo_id.exists'     => 'El tipo de arma seleccionado no es válido.',
            'nro_chaleco.max'         => 'El número de chaleco no puede superar los 50 caracteres.',
            'motivo_cambio.max'        => 'El motivo del cambio no puede superar los 1000 caracteres.',
        ];
    }
}
