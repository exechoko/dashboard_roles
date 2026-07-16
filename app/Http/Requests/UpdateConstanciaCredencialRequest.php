<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConstanciaCredencialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_apellido' => ['required', 'string', 'max:255'],
            'dni' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'email' => ['required', 'email', 'max:255'],
            'lugar' => ['nullable', 'string', 'max:255'],
            'fecha_entrega' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_apellido.required' => 'El nombre y apellido es obligatorio.',
            'dni.required' => 'El DNI es obligatorio.',
            'dni.regex' => 'El DNI debe contener solo números.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'fecha_entrega.required' => 'La fecha de entrega es obligatoria.',
        ];
    }
}
