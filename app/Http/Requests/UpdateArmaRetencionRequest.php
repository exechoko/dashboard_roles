<?php

namespace App\Http\Requests;

use App\Models\ArmaRetencion;
use Illuminate\Foundation\Http\FormRequest;

class UpdateArmaRetencionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editar-arma-retencion') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'personal_id'    => [
                'required',
                'exists:personals,id',
                function ($attribute, $value, $fail) {
                    $personal = \App\Models\Personal::find($value);
                    if (!$personal) {
                        $fail('El funcionario seleccionado no es válido.');
                        return;
                    }

                    $retencion = $this->route('armaRetencion');
                    if ((int) $value !== (int) $retencion?->personal_id && !$personal->tieneArmaAsignada()) {
                        $fail('El funcionario seleccionado no tiene un arma disponible para retener.');
                    }
                },
            ],
            'motivo_id'      => 'required|exists:arma_motivos,id',
            'fecha_posesion' => 'required|date',
            'observaciones'  => 'nullable|string|max:1000',
            'ciudad'         => 'nullable|string|in:' . implode(',', ArmaRetencion::CIUDADES),
            'hora_posesion'  => 'nullable|date_format:H:i',
            'marca_modelo'   => 'nullable|string|max:255',
            'estado_conservacion' => 'nullable|string|in:' . implode(',', ArmaRetencion::ESTADOS_CONSERVACION),
            'con_cargador'   => 'nullable|boolean',
            'con_cartucheria' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'personal_id.required'   => 'El funcionario es obligatorio.',
            'personal_id.exists'     => 'El funcionario seleccionado no es válido.',
            'motivo_id.required'     => 'El motivo es obligatorio.',
            'motivo_id.exists'       => 'El motivo seleccionado no es válido.',
            'fecha_posesion.required' => 'La fecha de posesión es obligatoria.',
            'fecha_posesion.date'    => 'La fecha de posesión debe ser una fecha válida.',
            'observaciones.max'      => 'Las observaciones no pueden superar los 1000 caracteres.',
            'ciudad.in'              => 'La ciudad seleccionada no es válida.',
            'hora_posesion.date_format' => 'La hora debe tener el formato HH:MM.',
            'marca_modelo.max'       => 'La marca/modelo no puede superar los 255 caracteres.',
            'estado_conservacion.in' => 'El estado de conservación seleccionado no es válido.',
        ];
    }
}
