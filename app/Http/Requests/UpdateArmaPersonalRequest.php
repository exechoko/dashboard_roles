<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'numeracion_arma' => 'required_if:cambiar_arma,1|nullable|string|max:50',
            'arma_tipo_id'   => 'required_if:cambiar_arma,1|nullable|exists:arma_tipos,id',
            'nro_chaleco'    => 'nullable|string|max:50',
            'cambiar_arma'   => 'nullable|boolean',
            'motivo_cambio'  => 'required_if:cambiar_arma,1|nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'jerarquia.required'      => 'La jerarquía es obligatoria.',
            'jerarquia.max'           => 'La jerarquía no puede superar los 100 caracteres.',
            'numeracion_arma.required_if' => 'La nueva numeración del arma es obligatoria cuando marca cambiar arma.',
            'numeracion_arma.max'     => 'La numeración del arma no puede superar los 50 caracteres.',
            'arma_tipo_id.required_if' => 'El tipo de arma es obligatorio cuando marca cambiar arma.',
            'arma_tipo_id.exists'     => 'El tipo de arma seleccionado no es válido.',
            'nro_chaleco.max'         => 'El número de chaleco no puede superar los 50 caracteres.',
            'motivo_cambio.required_if' => 'El motivo del cambio es obligatorio cuando marca cambiar arma.',
            'motivo_cambio.max'        => 'El motivo del cambio no puede superar los 1000 caracteres.',
        ];
    }
}
