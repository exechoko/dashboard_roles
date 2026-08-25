<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportarLlamadasCentralTelefonicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'archivos' => ['required', 'array', 'min:1'],
            'archivos.*' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivos.required' => 'Seleccioná al menos un archivo CSV.',
            'archivos.*.mimes' => 'El archivo :attribute debe ser un CSV.',
            'archivos.*.max' => 'El archivo :attribute no puede superar los 20MB.',
        ];
    }
}
