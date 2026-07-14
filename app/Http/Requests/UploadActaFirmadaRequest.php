<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadActaFirmadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acta_firmada' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'acta_firmada.required' => 'El archivo del acta firmada es obligatorio.',
            'acta_firmada.file' => 'El archivo debe ser un archivo válido.',
            'acta_firmada.mimes' => 'El archivo debe ser JPG, PNG, PDF, DOC o DOCX.',
            'acta_firmada.max' => 'El archivo no debe superar los 8 MB.',
        ];
    }
}
