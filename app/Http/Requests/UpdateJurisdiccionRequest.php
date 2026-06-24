<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJurisdiccionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editar-dependencia') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'puntos'       => 'required|array|min:3',
            'puntos.*.lat' => 'required|numeric|between:-90,90',
            'puntos.*.lng' => 'required|numeric|between:-180,180',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'puntos.required' => 'Debe dibujar un polígono con al menos 3 puntos.',
            'puntos.min'      => 'El polígono debe tener al menos 3 puntos.',
            'puntos.*.lat.required' => 'Cada punto debe tener latitud.',
            'puntos.*.lng.required' => 'Cada punto debe tener longitud.',
        ];
    }
}
