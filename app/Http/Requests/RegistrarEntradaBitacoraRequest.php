<?php

namespace App\Http\Requests;

use App\Models\RecursoBitacora;
use Illuminate\Foundation\Http\FormRequest;

class RegistrarEntradaBitacoraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('gestionar-flota-911') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'fecha_hora'      => ['required', 'date'],
            'categoria'       => ['required', 'in:' . implode(',', array_keys(RecursoBitacora::CATEGORIAS))],
            'descripcion'     => ['required', 'string', 'max:5000'],
            'estado'          => ['nullable', 'in:abierto,cerrado'],
            'km'              => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'taller'          => ['nullable', 'string', 'max:191'],
            'costo'           => ['nullable', 'numeric', 'min:0'],
            'poner_en_taller' => ['nullable', 'boolean'],
            'adjuntos'        => ['nullable', 'array', 'max:15'],
            'adjuntos.*'      => ['file', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_hora.required'  => 'La fecha y hora son obligatorias.',
            'categoria.required'   => 'Elegí una categoría.',
            'categoria.in'         => 'La categoría no es válida.',
            'descripcion.required' => 'Escribí la novedad.',
            'adjuntos.*.max'       => 'Cada archivo puede pesar hasta 10 MB.',
        ];
    }
}
