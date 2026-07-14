<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArmaPersonalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crear-personal') === true;
    }

    public function rules(): array
    {
        return [
            'nombre'        => 'required|string|max:100',
            'apellido'      => 'required|string|max:100',
            'lp'            => 'required|digits:5|unique:personals,lp',
            'jerarquia'     => 'required|string|max:100',
            'numeracion_arma' => 'required|string|max:50',
            'arma_tipo_id'  => 'required|exists:arma_tipos,id',
            'nro_chaleco'   => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'         => 'El nombre del funcionario es obligatorio.',
            'nombre.max'              => 'El nombre no puede superar los 100 caracteres.',
            'apellido.required'       => 'El apellido del funcionario es obligatorio.',
            'apellido.max'            => 'El apellido no puede superar los 100 caracteres.',
            'lp.required'             => 'El legajo policial (LP) es obligatorio.',
            'lp.digits'               => 'El legajo policial debe tener exactamente 5 dígitos.',
            'lp.unique'               => 'Ya existe un funcionario con ese legajo policial.',
            'jerarquia.required'      => 'La jerarquía es obligatoria.',
            'jerarquia.max'           => 'La jerarquía no puede superar los 100 caracteres.',
            'numeracion_arma.required' => 'La numeración del arma es obligatoria.',
            'numeracion_arma.max'     => 'La numeración del arma no puede superar los 50 caracteres.',
            'arma_tipo_id.required'   => 'El tipo de arma es obligatorio.',
            'arma_tipo_id.exists'     => 'El tipo de arma seleccionado no es válido.',
            'nro_chaleco.max'         => 'El número de chaleco no puede superar los 50 caracteres.',
        ];
    }
}
