<?php

namespace App\Http\Requests;

use App\Models\Personal;
use App\Models\RecursoEstadoDiario;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class GuardarParteDiarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('generar-parte-diario') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $estados = implode(',', array_keys(RecursoEstadoDiario::$estados));

        return [
            'fecha'        => ['required', 'date'],
            'guardia'      => ['required', 'in:guardia_1,guardia_2,guardia_3,guardia_4'],
            'horario'      => ['required', 'in:06_18,18_06'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin'    => ['required', 'date', 'after:fecha_inicio'],

            'novedades_generales' => ['nullable', 'string', 'max:3000'],
            'novedades'           => ['nullable', 'array'],
            'novedades.*'         => ['nullable', 'string', 'max:2000'],

            'secciones'                        => ['nullable', 'array'],
            'secciones.*.guardia_interna'      => ['nullable', 'string', 'max:1000'],
            'secciones.*.licencia_ordinaria'   => ['nullable', 'string', 'max:1000'],
            'secciones.*.novedades_pie'        => ['nullable', 'string', 'max:2000'],
            'secciones.*.asignaciones'         => ['nullable', 'array'],
            'secciones.*.asignaciones.*.grupo' => ['nullable', 'string', 'max:80'],
            'secciones.*.asignaciones.*.nombre' => ['required_with:secciones.*.asignaciones.*.asignacion_texto', 'nullable', 'string', 'max:120'],
            'secciones.*.asignaciones.*.asignacion_texto' => ['nullable', 'string', 'max:255'],

            'recursos'              => ['nullable', 'array'],
            'recursos.*.id'         => ['required', 'exists:recursos,id'],
            'recursos.*.estado_dia' => ['required', 'in:' . $estados],
            'recursos.*.motivo'     => ['nullable', 'string', 'max:500'],
            'recursos.*.zona'       => ['nullable', 'integer', 'min:1', 'max:4'],
            'recursos.*.ht'         => ['nullable', 'string', 'max:50'],
            'recursos.*.dotacion'   => ['nullable', 'array'],
            'recursos.*.dotacion.*' => ['integer', 'exists:personals,id'],
            'recursos.*.chofer_id'  => ['nullable', 'integer', 'exists:personals,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha.required'        => 'La fecha del parte es obligatoria.',
            'guardia.required'      => 'Seleccione la guardia.',
            'guardia.in'            => 'La guardia seleccionada no es válida.',
            'horario.in'            => 'El horario seleccionado no es válido.',
            'fecha_fin.after'       => 'El fin del turno debe ser posterior al inicio.',
            'recursos.*.estado_dia.in'  => 'El estado del día de un recurso no es válido.',
            'recursos.*.zona.min'       => 'La zona debe estar entre 1 y 4.',
            'recursos.*.zona.max'       => 'La zona debe estar entre 1 y 4.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ids = $this->personalDuplicado();

            if ($ids->isNotEmpty()) {
                $nombres = Personal::whereIn('id', $ids)
                    ->get()
                    ->map(fn (Personal $p) => $p->getNombreCompletoAttribute())
                    ->join(', ');

                $validator->errors()->add(
                    'dotacion',
                    "El siguiente personal está asignado a más de un recurso: {$nombres}. "
                    . 'Cada funcionario puede figurar en la dotación de un solo recurso por parte.'
                );
            }
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function personalDuplicado(): Collection
    {
        return collect($this->input('recursos', []))
            ->flatMap(fn ($datos) => $datos['dotacion'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->duplicates()
            ->unique()
            ->values();
    }
}
