<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarActivacionTotemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar-activacion-totem') === true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'camara_id' => 'nullable|exists:camaras,id',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }
}
