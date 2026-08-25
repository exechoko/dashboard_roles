<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReporteLlamadasCentralTelefonicaRequest extends FormRequest
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
            'desde' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:hasta'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'periodo' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'desde.before_or_equal' => 'La fecha desde no puede ser posterior a la fecha hasta.',
            'hasta.after_or_equal' => 'La fecha hasta no puede ser anterior a la fecha desde.',
            'periodo.regex' => 'El período seleccionado no es válido.',
        ];
    }
}
