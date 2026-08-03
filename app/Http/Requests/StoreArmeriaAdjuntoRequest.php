<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArmeriaAdjuntoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar-armeria') === true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Debe seleccionar un archivo.',
            'archivo.mimes' => 'Formatos permitidos: JPG, PNG, WEBP, PDF, DOC o DOCX.',
            'archivo.max' => 'El archivo no debe superar los 8 MB.',
        ];
    }
}
