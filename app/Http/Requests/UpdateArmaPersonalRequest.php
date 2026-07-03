<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArmaPersonalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar-personal') === true;
    }

    public function rules(): array
    {
        return [
            'jerarquia' => 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'jerarquia.required' => 'La jerarquía es obligatoria.',
            'jerarquia.max'      => 'La jerarquía no puede superar los 100 caracteres.',
        ];
    }
}
