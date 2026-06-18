<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoticiaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editar-noticia') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo'            => 'required|string|max:200',
            'bajada'            => 'nullable|string|max:300',
            'cuerpo'            => 'required|string',
            'fecha_publicacion' => 'required|date',
            'publicada'         => 'nullable|boolean',
            'imagenes'          => 'nullable|array|max:20',
            'imagenes.*'        => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'eliminar'          => 'nullable|array',
            'eliminar.*'        => 'integer',
            'miniatura'         => 'nullable|string|max:10',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulo.required'            => 'El título es obligatorio.',
            'cuerpo.required'            => 'El cuerpo de la noticia es obligatorio.',
            'fecha_publicacion.required' => 'La fecha de publicación es obligatoria.',
            'imagenes.*.image'           => 'Cada archivo debe ser una imagen.',
            'imagenes.*.mimes'           => 'Formatos permitidos: jpg, png o webp.',
            'imagenes.*.max'             => 'Cada imagen no puede superar los 5 MB.',
        ];
    }
}
