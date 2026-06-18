<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebHistoriaCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editar-web-historia') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'anio'   => 'required|string|max:40',
            'titulo' => 'required|string|max:200',
            'texto'  => 'required|string|max:5000',
            'tag'        => 'nullable|string|max:60',
            'imagenes'   => 'nullable|array|max:3',
            'imagenes.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'orden'      => 'nullable|integer|min:0|max:9999',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'anio.required'    => 'El año (o período) es obligatorio.',
            'titulo.required'  => 'El título es obligatorio.',
            'texto.required'   => 'El texto es obligatorio.',
            'imagenes.max'     => 'Podés subir hasta 3 imágenes por tarjeta.',
            'imagenes.*.image' => 'Cada archivo debe ser una imagen.',
            'imagenes.*.mimes' => 'Formatos permitidos: jpg, png o webp.',
            'imagenes.*.max'   => 'Cada imagen no puede superar los 5 MB.',
        ];
    }
}
