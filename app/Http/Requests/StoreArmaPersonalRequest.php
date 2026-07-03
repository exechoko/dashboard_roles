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
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'lp'       => 'required|digits:5|unique:personals,lp',
            'jerarquia'=> 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'    => 'El nombre del funcionario es obligatorio.',
            'nombre.max'         => 'El nombre no puede superar los 100 caracteres.',
            'apellido.required'  => 'El apellido del funcionario es obligatorio.',
            'apellido.max'       => 'El apellido no puede superar los 100 caracteres.',
            'lp.required'        => 'El legajo policial (LP) es obligatorio.',
            'lp.digits'          => 'El legajo policial debe tener exactamente 5 dígitos.',
            'lp.unique'          => 'Ya existe un funcionario con ese legajo policial.',
            'jerarquia.required' => 'La jerarquía es obligatoria.',
            'jerarquia.max'      => 'La jerarquía no puede superar los 100 caracteres.',
        ];
    }
}
