<?php

namespace App\Http\Requests;

use App\Models\ArmeriaArma;
use Illuminate\Foundation\Http\FormRequest;

class StoreArmeriaArmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crear-armeria') === true;
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
            'numero_serie' => 'required|string|max:50|unique:armeria_armas,numero_serie',
            'estado' => 'nullable|string|in:' . implode(',', ArmeriaArma::ESTADOS),
            'ubicacion' => 'nullable|string|in:' . implode(',', ArmeriaArma::UBICACIONES),
            'observaciones' => 'nullable|string|max:1000',
            'comentario' => 'nullable|string|max:500',
            'fecha' => 'nullable|date|before_or_equal:now',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de arma es obligatorio.',
            'tipo.in' => 'El tipo de arma seleccionado no es válido.',
            'numero_serie.required' => 'El número de serie es obligatorio.',
            'numero_serie.unique' => 'Ya existe un arma registrada con ese número de serie.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'ubicacion.in' => 'La ubicación seleccionada no es válida.',
            'fecha.date' => 'La fecha de carga debe ser una fecha y hora válidas.',
            'fecha.before_or_equal' => 'La fecha de carga no puede ser futura.',
        ];
    }
}
