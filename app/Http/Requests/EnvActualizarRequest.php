<?php

namespace App\Http\Requests;

use App\Services\EnvEditorService;
use App\Support\ConfiguracionCatalogo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Guardado de la pantalla "Variables de entorno": acepta cualquier clave ya
 * presente en el .env (catalogada o no, pestaña "Avanzado"), rechaza claves
 * bloqueadas, y exige el permiso extra para tocar el grupo crítico.
 */
class EnvActualizarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar-configuracion-env') === true;
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
            $existentes = array_keys(app(EnvEditorService::class)->pares());
            $puedeCritico = $this->user()?->can('editar-configuracion-env-critico') === true;

            foreach (array_keys((array) $this->input('valores', [])) as $clave) {
                if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $clave)) {
                    $validator->errors()->add('valores', "Nombre de clave inválido: {$clave}");
                    continue;
                }

                if (ConfiguracionCatalogo::estaBloqueada($clave)) {
                    $validator->errors()->add('valores', "La clave {$clave} no se puede editar desde acá.");
                    continue;
                }

                if (!ConfiguracionCatalogo::existeEnCatalogo($clave) && !in_array($clave, $existentes, true)) {
                    $validator->errors()->add('valores', "Clave desconocida: {$clave}");
                    continue;
                }

                if (ConfiguracionCatalogo::esCritica($clave) && !$puedeCritico) {
                    $validator->errors()->add('valores', "No tenés permiso para editar {$clave} (grupo crítico).");
                }
            }
        });
    }
}
