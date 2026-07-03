<?php

namespace App\Http\Requests;

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
            'personal_id'      => 'required|exists:personals,id',
            'numeracion_arma'  => 'required|string|max:50',
            'nro_chaleco'      => 'nullable|string|max:50',
            'motivo_id'        => 'required|exists:arma_motivos,id',
            'fecha_posesion'   => 'required|date',
            'observaciones'    => 'nullable|string|max:1000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'personal_id.required'     => 'El funcionario es obligatorio.',
            'personal_id.exists'       => 'El funcionario seleccionado no es válido.',
            'numeracion_arma.required' => 'La numeración del arma es obligatoria.',
            'numeracion_arma.max'      => 'La numeración del arma no puede superar los 50 caracteres.',
            'nro_chaleco.max'          => 'El número de chaleco no puede superar los 50 caracteres.',
            'motivo_id.required'       => 'El motivo es obligatorio.',
            'motivo_id.exists'         => 'El motivo seleccionado no es válido.',
            'fecha_posesion.required'  => 'La fecha de posesión es obligatoria.',
            'fecha_posesion.date'      => 'La fecha de posesión debe ser una fecha válida.',
            'observaciones.max'        => 'Las observaciones no pueden superar los 1000 caracteres.',
        ];
    }
}
