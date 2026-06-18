<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebContadoresRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editar-web-contadores') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'anosServicio'        => 'required|integer|min:0|max:1000',
            'funcionarios'        => 'required|integer|min:0|max:100000',
            'camaras'             => 'required|integer|min:0|max:1000000',
            'moviles'             => 'required|integer|min:0|max:100000',
            'motopatrullas'       => 'required|integer|min:0|max:100000',
            'unidadesOperativas'  => 'required|integer|min:0|max:100000',
            'llamadasPromedio'    => 'required|integer|min:0|max:1000000',
            'dispositivosDuales'  => 'required|integer|min:0|max:1000000',
            'usuariosBotonPanico' => 'required|integer|min:0|max:1000000',

            'meses2026'           => 'required|array|min:1|max:24',
            'meses2026.*'         => 'required|string|max:12',

            'armasPorMes'         => 'required|array',
            'armasPorMes.*'       => 'required|integer|min:0|max:100000',
            'vehiculosPorMes'     => 'required|array',
            'vehiculosPorMes.*'   => 'required|integer|min:0|max:100000',
            'motosPorMes'         => 'required|array',
            'motosPorMes.*'       => 'required|integer|min:0|max:100000',
        ];
    }

    /**
     * Verifica que cada serie mensual tenga exactamente un valor por mes.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $meses = $this->input('meses2026', []);
            $cantidadMeses = is_array($meses) ? count($meses) : 0;

            foreach (['armasPorMes', 'vehiculosPorMes', 'motosPorMes'] as $serie) {
                $valores = $this->input($serie, []);
                if (! is_array($valores) || count($valores) !== $cantidadMeses) {
                    $validator->errors()->add($serie, "Debe tener un valor por cada mes ({$cantidadMeses}).");
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.required'        => 'Este campo es obligatorio.',
            '*.integer'         => 'Debe ser un número entero.',
            '*.min'             => 'No puede ser negativo.',
            'meses2026.min'     => 'Debe haber al menos un mes.',
            'meses2026.*.max'   => 'El nombre del mes es demasiado largo.',
        ];
    }
}
