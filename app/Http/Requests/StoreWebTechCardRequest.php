<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebTechCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editar-web-tecnologia') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:200',
            'texto'  => 'required|string|max:6000',
            'color'  => ['required', Rule::in(array_keys(config('landing.tecnologia_colores', [])))],
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'orden'  => 'nullable|integer|min:0|max:9999',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'texto.required'  => 'El texto es obligatorio.',
            'color.required'  => 'Elegí un color.',
            'color.in'        => 'El color seleccionado no es válido.',
            'imagen.image'    => 'El archivo debe ser una imagen.',
            'imagen.mimes'    => 'Formatos permitidos: jpg, png o webp.',
            'imagen.max'      => 'La imagen no puede superar los 5 MB.',
        ];
    }
}
