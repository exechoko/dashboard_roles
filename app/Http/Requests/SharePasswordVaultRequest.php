<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SharePasswordVaultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('compartir-clave') === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('shared_with_user_id') && !$this->filled('shared_with_user_ids')) {
            $this->merge([
                'shared_with_user_ids' => [$this->input('shared_with_user_id')],
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shared_with_user_ids' => ['required', 'array', 'min:1'],
            'shared_with_user_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:users,id',
                Rule::notIn([$this->user()?->id]),
            ],
            'can_edit' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'shared_with_user_ids.required' => 'Seleccioná al menos un usuario para compartir.',
            'shared_with_user_ids.array' => 'La selección de usuarios no es válida.',
            'shared_with_user_ids.min' => 'Seleccioná al menos un usuario para compartir.',
            'shared_with_user_ids.*.distinct' => 'No repitas usuarios en la selección.',
            'shared_with_user_ids.*.exists' => 'Uno de los usuarios seleccionados no existe.',
            'shared_with_user_ids.*.not_in' => 'No podés compartir una contraseña con vos mismo.',
        ];
    }
}
