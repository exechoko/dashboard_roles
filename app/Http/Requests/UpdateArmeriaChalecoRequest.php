<?php

namespace App\Http\Requests;

use App\Models\ArmeriaChaleco;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArmeriaChalecoRequest extends FormRequest
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
            'movil' => 'nullable|string|max:50',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:150',
            'talle' => 'nullable|string|in:' . implode(',', ArmeriaChaleco::TALLES),
            'numero_serie' => [
                'required', 'string', 'max:50',
                Rule::unique('armeria_chalecos', 'numero_serie')->ignore($this->route('armeriaChaleco')),
            ],
            'observaciones' => 'nullable|string|max:1000',
            'comentario' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'numero_serie.required' => 'El número de serie es obligatorio.',
            'numero_serie.unique' => 'Ya existe un chaleco registrado con ese número de serie.',
            'talle.in' => 'El talle seleccionado no es válido.',
        ];
    }
}
