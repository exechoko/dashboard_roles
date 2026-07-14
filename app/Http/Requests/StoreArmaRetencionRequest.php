<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArmaRetencionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('crear-arma-retencion') === true;
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
                    if (!$personal->tieneArmaAsignada()) {
                        $fail('El funcionario no tiene un arma asignada. Asigne un arma al funcionario primero.');
                    }
                    if ($personal->tieneRetencionActiva()) {
                        $fail('El funcionario ya tiene una retención activa. Debe finalizarla antes de crear una nueva.');
                    }
                },
            ],
            'motivo_id'      => 'required|exists:arma_motivos,id',
            'fecha_posesion' => 'required|date',
            'observaciones'  => 'nullable|string|max:1000',
            'comentario'     => 'nullable|string|max:500',
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
            'comentario.max'         => 'El comentario no debe superar los 500 caracteres.',
        ];
    }
}
