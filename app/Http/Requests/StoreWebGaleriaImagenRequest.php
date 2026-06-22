<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebGaleriaImagenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editar-web-galeria') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo'    => 'required|string|max:200',
            'categoria' => 'nullable|string|max:100',
            'imagen'    => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
            'orden'     => 'nullable|integer|min:0|max:9999',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulo.required' => 'El título/descripción es obligatorio.',
            'imagen.required' => 'Tenés que elegir una imagen.',
            'imagen.image'    => 'El archivo debe ser una imagen.',
            'imagen.mimes'    => 'Formatos permitidos: jpg, png o webp.',
            'imagen.max'      => 'La imagen no puede superar los 8 MB.',
        ];
    }
}
