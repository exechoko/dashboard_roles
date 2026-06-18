<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebDependenciaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editar-web-dependencias') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre'    => 'required|string|max:150',
            'categoria' => ['required', Rule::in(array_keys(config('landing.dependencias_categorias', [])))],
            'direccion' => 'nullable|string|max:200',
            'telefonos' => 'nullable|string|max:300',
            'tags'      => 'nullable|string|max:300',
            'orden'     => 'nullable|integer|min:0|max:9999',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required'   => 'El nombre es obligatorio.',
            'categoria.required' => 'Elegí una categoría.',
            'categoria.in'      => 'La categoría seleccionada no es válida.',
        ];
    }
}
