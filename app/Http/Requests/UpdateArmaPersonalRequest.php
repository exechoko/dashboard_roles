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
            'numeracion_arma' => 'required|string|max:50',
            'arma_tipo_id'    => 'required|exists:arma_tipos,id',
            'nro_chaleco'     => 'nullable|string|max:50',
            'motivo_cambio'   => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'numeracion_arma.required' => 'La numeración del arma es obligatoria.',
            'numeracion_arma.max'      => 'La numeración del arma no puede superar los 50 caracteres.',
            'arma_tipo_id.required'    => 'El tipo de arma es obligatorio.',
            'arma_tipo_id.exists'      => 'El tipo de arma seleccionado no es válido.',
            'nro_chaleco.max'          => 'El número de chaleco no puede superar los 50 caracteres.',
            'motivo_cambio.required'   => 'El motivo del cambio es obligatorio.',
            'motivo_cambio.max'        => 'El motivo del cambio no puede superar los 1000 caracteres.',
        ];
    }
}
