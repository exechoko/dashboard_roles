<?php

namespace App\Http\Requests;

use App\Support\ConfiguracionCatalogo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class IaActualizarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar-configuracion-ia') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'valores'   => 'array',
            'valores.*' => 'nullable|string|max:2000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $permitidas = ConfiguracionCatalogo::clavesDeGrupo('ia');

            foreach (array_keys((array) $this->input('valores', [])) as $clave) {
                if (!in_array($clave, $permitidas, true)) {
                    $validator->errors()->add('valores', "Clave no válida para esta pantalla: {$clave}");
                }
            }
        });
    }
}
