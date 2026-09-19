<?php

namespace App\Http\Requests;

use App\Models\DominioAlerta;
use Illuminate\Foundation\Http\FormRequest;

class StoreDominioAlertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crear-alerta-dominio') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dominio' => DominioAlerta::normalizar($this->dominio),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dominio' => 'required|string|max:15|unique:dominios_alerta,dominio',
            'parcial' => 'nullable|boolean',
            'marca' => 'required|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'color' => 'required|string|max:50',
            'motivo' => 'nullable|string|max:1000',
            'solicitado_por' => 'required|string|max:150',
            'funcionario_carga' => 'required|string|max:150',
            'notificar_a' => 'required|string|max:255',
            'camara_texto' => 'nullable|string|max:10000',
            'fecha_hecho' => 'nullable|date|before_or_equal:today',
            'observaciones' => 'nullable|string|max:2000',
            'comentario' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'dominio.required' => 'El dominio (patente) es obligatorio.',
            'dominio.unique' => 'Ya existe un registro cargado con ese dominio.',
            'marca.required' => 'La marca es obligatoria.',
            'color.required' => 'El color es obligatorio.',
            'solicitado_por.required' => 'El campo "Solicitado por" es obligatorio.',
            'funcionario_carga.required' => 'El campo "Funcionario que carga" es obligatorio.',
            'notificar_a.required' => 'El campo "Notificar / Avisar a" es obligatorio.',
            'fecha_hecho.before_or_equal' => 'La fecha del hecho no puede ser futura.',
        ];
    }
}
