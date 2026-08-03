<?php

namespace App\Http\Requests;

use App\Models\ArmeriaArma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArmeriaArmaRequest extends FormRequest
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
            'tipo' => 'required|string|in:' . implode(',', ArmeriaArma::TIPOS),
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:150',
            'numero_serie' => [
                'required', 'string', 'max:50',
                Rule::unique('armeria_armas', 'numero_serie')->ignore($this->route('armeriaArma')),
            ],
            'observaciones' => 'nullable|string|max:1000',
            'comentario' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de arma es obligatorio.',
            'tipo.in' => 'El tipo de arma seleccionado no es válido.',
            'numero_serie.required' => 'El número de serie es obligatorio.',
            'numero_serie.unique' => 'Ya existe un arma registrada con ese número de serie.',
        ];
    }
}
