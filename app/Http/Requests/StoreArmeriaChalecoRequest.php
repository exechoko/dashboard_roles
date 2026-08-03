<?php

namespace App\Http\Requests;

use App\Models\ArmeriaChaleco;
use Illuminate\Foundation\Http\FormRequest;

class StoreArmeriaChalecoRequest extends FormRequest
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
            'movil' => 'nullable|string|max:50',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:150',
            'talle' => 'nullable|string|in:' . implode(',', ArmeriaChaleco::TALLES),
            'numero_serie' => 'required|string|max:50|unique:armeria_chalecos,numero_serie',
            'estado' => 'nullable|string|in:' . implode(',', ArmeriaChaleco::ESTADOS),
            'ubicacion' => 'nullable|string|in:' . implode(',', ArmeriaChaleco::UBICACIONES),
            'observaciones' => 'nullable|string|max:1000',
            'comentario' => 'nullable|string|max:500',
            'fecha' => 'nullable|date|before_or_equal:now',
        ];
    }

    public function messages(): array
    {
        return [
            'numero_serie.required' => 'El número de serie es obligatorio.',
            'numero_serie.unique' => 'Ya existe un chaleco registrado con ese número de serie.',
            'talle.in' => 'El talle seleccionado no es válido.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'ubicacion.in' => 'La ubicación seleccionada no es válida.',
            'fecha.date' => 'La fecha de carga debe ser una fecha y hora válidas.',
            'fecha.before_or_equal' => 'La fecha de carga no puede ser futura.',
        ];
    }
}
