<?php

namespace App\Http\Requests;

use App\Models\PersonaAlerta;
use Illuminate\Foundation\Http\FormRequest;

class StorePersonaAlertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crear-alerta-persona') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dni' => PersonaAlerta::normalizarDni($this->dni),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dni' => 'nullable|string|max:20|unique:personas_alerta,dni',
            'apellido_nombre' => 'required|string|max:150',
            'direccion' => 'nullable|string|max:255',
            'motivo' => 'nullable|string|max:1000',
            'solicitado_por' => 'required|string|max:150',
            'funcionario_carga' => 'required|string|max:150',
            'notificar_a' => 'required|string|max:255',
            'identificado' => 'nullable|boolean',
            'finalizado' => 'nullable|boolean',
            'activo' => 'nullable|boolean',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'fecha_hecho' => 'nullable|date|before_or_equal:today',
            'observaciones' => 'nullable|string|max:2000',
            'comentario' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'apellido_nombre.required' => 'El apellido y nombre es obligatorio.',
            'dni.unique' => 'Ya existe una persona cargada con ese D.N.I.',
            'solicitado_por.required' => 'El campo "Solicitado por" es obligatorio.',
            'funcionario_carga.required' => 'El campo "Funcionario que carga" es obligatorio.',
            'notificar_a.required' => 'El campo "Notificar / Avisar a" es obligatorio.',
            'foto.image' => 'La foto debe ser una imagen (JPG, PNG o WEBP).',
            'foto.max' => 'La foto no debe superar los 4 MB.',
            'fecha_hecho.before_or_equal' => 'La fecha del hecho no puede ser futura.',
        ];
    }
}
