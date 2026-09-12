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
            'dni'           => 'nullable|digits_between:7,8|unique:personals,dni',
            'jerarquia'     => 'required|string|max:100',
            'direccion'       => 'nullable|string|max:255',
            'telefono'        => 'nullable|string|max:100',
            'email'           => 'nullable|email|max:150',
            'estado_civil'    => 'nullable|string|max:50',
            'fecha_nacimiento' => 'nullable|date|before:today',
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
            'dni.digits_between'      => 'El DNI debe tener entre 7 y 8 dígitos.',
            'dni.unique'              => 'Ya existe un funcionario con ese DNI.',
            'jerarquia.required'      => 'La jerarquía es obligatoria.',
            'jerarquia.max'           => 'La jerarquía no puede superar los 100 caracteres.',
            'direccion.max'           => 'La dirección no puede superar los 255 caracteres.',
            'telefono.max'            => 'El teléfono no puede superar los 100 caracteres.',
            'email.email'             => 'El email no es válido.',
            'email.max'               => 'El email no puede superar los 150 caracteres.',
            'estado_civil.max'        => 'El estado civil no puede superar los 50 caracteres.',
            'fecha_nacimiento.date'   => 'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'numeracion_arma.required' => 'La numeración del arma es obligatoria.',
            'numeracion_arma.max'     => 'La numeración del arma no puede superar los 50 caracteres.',
            'arma_tipo_id.required'   => 'El tipo de arma es obligatorio.',
            'arma_tipo_id.exists'     => 'El tipo de arma seleccionado no es válido.',
            'nro_chaleco.max'         => 'El número de chaleco no puede superar los 50 caracteres.',
        ];
    }
}
