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

            'armasPorMes'         => 'required|array|size:6',
            'armasPorMes.*'       => 'required|integer|min:0|max:100000',
            'vehiculosPorMes'     => 'required|array|size:6',
            'vehiculosPorMes.*'   => 'required|integer|min:0|max:100000',
            'motosPorMes'         => 'required|array|size:6',
            'motosPorMes.*'       => 'required|integer|min:0|max:100000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.required'           => 'Este campo es obligatorio.',
            '*.integer'            => 'Debe ser un número entero.',
            '*.min'                => 'No puede ser negativo.',
            'armasPorMes.size'     => 'Debe haber un valor por cada uno de los 6 meses.',
            'vehiculosPorMes.size' => 'Debe haber un valor por cada uno de los 6 meses.',
            'motosPorMes.size'     => 'Debe haber un valor por cada uno de los 6 meses.',
        ];
    }
}
