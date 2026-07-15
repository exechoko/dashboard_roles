<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketPgRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipo_equipo'        => ['required', 'string', Rule::in(config('ticketera_categorias.categorias'))],
            'modelo_equipo'      => 'nullable|string|max:80',
            'movil'              => 'nullable|string|max:80',
            'recurso_id'         => 'nullable|integer|exists:recursos,id',
            'equipo_id'          => 'nullable|integer|exists:equipos,id',
            'tipo_terminal_id'   => 'nullable|integer|exists:tipo_terminales,id',
            'dependencia'        => 'nullable|string|max:160',
            'oficina'            => 'nullable|string|max:160',
            'problema_detectado' => 'required|string|max:250',
            'fecha_inicio_falla' => 'nullable|date',
            'fecha_fin_falla'    => 'nullable|date|after_or_equal:fecha_inicio_falla',
            'prioridad'          => 'required|string|max:40',
            'subsistema'         => ['required', 'string', Rule::in(config('ticketera_categorias.subsistemas'))],
            'camaras'            => 'nullable|array',
            'camaras.*'          => 'integer|exists:camaras,id',
            'cantidad_items'     => 'nullable|integer|min:0',
            'periodo_facturado'  => 'nullable|string|max:30',
            'estado_ticketera'   => 'nullable|string|max:80',
            'aplica_calculo'     => 'nullable|boolean',
            'observaciones'      => 'nullable|string|max:2000',
            'asunto'             => 'nullable|string|max:200',
            'texto_enviado'      => 'nullable|string|max:10000',
            'accion'             => 'nullable|in:guardar,enviar',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_equipo.required'            => 'La categoría es obligatoria.',
            'tipo_equipo.in'                  => 'La categoría seleccionada no es válida.',
            'problema_detectado.required'     => 'El problema detectado es obligatorio.',
            'prioridad.required'              => 'La prioridad es obligatoria.',
            'subsistema.required'             => 'El subsistema es obligatorio.',
            'subsistema.in'                   => 'El subsistema seleccionado no es válido.',
            'fecha_fin_falla.after_or_equal'  => 'La fecha de fin de falla no puede ser anterior al inicio.',
            'camaras.*.exists'                => 'Alguna de las cámaras seleccionadas no existe.',
        ];
    }
}
