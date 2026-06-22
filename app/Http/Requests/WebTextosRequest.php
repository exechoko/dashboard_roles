<?php

namespace App\Http\Requests;

use App\Services\GeneradorConfigTextos;
use Illuminate\Foundation\Http\FormRequest;

class WebTextosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editar-web-textos') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'textos'   => 'required|array',
            'textos.*' => 'required|string|max:20000',
        ];
    }

    /**
     * Solo se aceptan las claves declaradas en el catálogo.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $permitidas = array_keys(GeneradorConfigTextos::catalogo());

        $validator->after(function (\Illuminate\Validation\Validator $validator) use ($permitidas): void {
            foreach (array_keys((array) $this->input('textos', [])) as $clave) {
                if (! in_array($clave, $permitidas, true)) {
                    $validator->errors()->add('textos', "Clave de texto no válida: {$clave}");
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
            'textos.*.required' => 'El texto no puede quedar vacío.',
            'textos.*.max'      => 'El texto es demasiado largo (máx. 20000 caracteres).',
        ];
    }
}
