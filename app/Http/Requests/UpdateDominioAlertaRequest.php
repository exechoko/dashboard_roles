<?php

namespace App\Http\Requests;

use App\Models\DominioAlerta;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDominioAlertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar-alerta-dominio') === true;
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
            'dominio' => [
                'required', 'string', 'max:15',
                Rule::unique('dominios_alerta', 'dominio')->ignore($this->route('dominioAlerta')),
            ],
            'parcial' => 'nullable|boolean',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'motivo' => 'nullable|string|max:1000',
            'solicitado_por' => 'nullable|string|max:150',
            'funcionario_carga' => 'nullable|string|max:150',
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
            'fecha_hecho.before_or_equal' => 'La fecha del hecho no puede ser futura.',
        ];
    }
}
