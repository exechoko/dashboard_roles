<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarHistorialHashRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('ver-hash-archivo') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre_archivo' => ['required', 'string', 'max:255'],
            'cifrado_aplicado' => ['required', Rule::in(['SHA-256'])],
            'hash' => ['required', 'string', 'size:64', 'regex:/\A[a-f0-9]{64}\z/i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre_archivo.required' => 'El nombre del archivo es obligatorio.',
            'nombre_archivo.max' => 'El nombre del archivo no puede superar los 255 caracteres.',
            'cifrado_aplicado.in' => 'El algoritmo aplicado no es válido.',
            'hash.size' => 'El hash SHA-256 debe tener 64 caracteres.',
            'hash.regex' => 'El hash contiene caracteres no válidos.',
        ];
    }
}
