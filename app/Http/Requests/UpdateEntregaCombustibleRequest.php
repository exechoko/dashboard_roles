<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntregaCombustibleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fecha_entrega' => ['required', 'date'],
            'hora_entrega' => ['required', 'date_format:H:i'],
            'ticket' => ['required', 'string', 'max:255'],
            'empresa_soporte' => ['required', 'string', 'max:255'],
            'personal_receptor' => ['required', 'string', 'max:255'],
            'legajo_receptor' => ['nullable', 'string', 'max:50'],
            'personal_entrega' => ['required', 'string', 'max:255'],
            'legajo_entrega' => ['nullable', 'string', 'max:50'],
            'cantidad_litros' => ['required', 'integer', 'min:1', 'max:9999'],
            'cantidad_bidones' => ['required', 'integer', 'min:1', 'max:999'],
            'litros_por_bidon' => ['required', 'integer', 'min:1', 'max:999'],
            'combustible' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_entrega.required' => 'La fecha de entrega es obligatoria.',
            'hora_entrega.required' => 'La hora de entrega es obligatoria.',
            'ticket.required' => 'El ticket solicitado por soporte es obligatorio.',
            'empresa_soporte.required' => 'La empresa de soporte es obligatoria.',
            'personal_receptor.required' => 'El personal receptor es obligatorio.',
            'personal_entrega.required' => 'El personal que entrega es obligatorio.',
            'cantidad_litros.required' => 'La cantidad de litros es obligatoria.',
            'cantidad_bidones.required' => 'La cantidad de bidones es obligatoria.',
            'litros_por_bidon.required' => 'Los litros por bidón son obligatorios.',
            'combustible.required' => 'El tipo de combustible es obligatorio.',
        ];
    }
}
