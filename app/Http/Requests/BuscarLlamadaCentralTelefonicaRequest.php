<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuscarLlamadaCentralTelefonicaRequest extends FormRequest
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
            'numero' => ['nullable', 'string', 'max:30'],
            'desde' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:hasta'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'tipo' => ['nullable', 'in:recibida,saliente,interna,otra'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero.max' => 'El número no puede tener más de 30 caracteres.',
            'desde.before_or_equal' => 'La fecha desde no puede ser posterior a la fecha hasta.',
            'hasta.after_or_equal' => 'La fecha hasta no puede ser anterior a la fecha desde.',
        ];
    }
}
