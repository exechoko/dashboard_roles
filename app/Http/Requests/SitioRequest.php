<?php

namespace App\Http\Requests;

use App\Models\Sitio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SitioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $permission = $this->isMethod('post') ? 'crear-sitio' : 'editar-sitio';

        return $this->user()?->can($permission) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required'],
            'localidad' => ['required', 'not_in:Seleccionar localidad'],
            'destino_id' => ['required', 'not_in:Seleccionar la dependencia'],
            'activo' => ['required', 'boolean'],
            'energizado_por' => ['nullable', 'string', Rule::in(Sitio::ENERGIZADO_POR)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es necesario completar.',
            'energizado_por.in' => 'La empresa o municipio seleccionado no es válido.',
        ];
    }
}
