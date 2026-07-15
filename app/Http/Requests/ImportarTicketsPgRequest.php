<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportarTicketsPgRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'archivo' => 'required|file|mimes:xlsx,xlsm,xls|max:20480',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo.required' => 'Debe seleccionar el archivo Excel de control de incidencias.',
            'archivo.mimes'    => 'El archivo debe ser un Excel (.xlsx, .xlsm o .xls).',
            'archivo.max'      => 'El archivo no puede superar los 20 MB.',
        ];
    }
}
