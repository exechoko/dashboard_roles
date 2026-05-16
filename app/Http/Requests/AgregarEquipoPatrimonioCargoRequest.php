<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgregarEquipoPatrimonioCargoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('firmar-patrimonio-cargos') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'flota_id' => 'required|integer|exists:flota_general,id',
        ];
    }

    public function messages(): array
    {
        return [
            'flota_id.required' => 'Debe seleccionar un equipo para agregar.',
            'flota_id.exists'   => 'El equipo seleccionado no existe.',
        ];
    }
}
