<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkSharePasswordVaultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('compartir-clave') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password_vault_ids' => ['required', 'array', 'min:1'],
            'password_vault_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('password_vaults', 'id')->where(function ($query) {
                    return $query->where('user_id', $this->user()?->id)
                        ->whereNull('deleted_at');
                }),
            ],
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
            'password_vault_ids.required' => 'Seleccioná al menos una contraseña para compartir.',
            'password_vault_ids.min' => 'Seleccioná al menos una contraseña para compartir.',
            'password_vault_ids.*.exists' => 'Solo podés compartir contraseñas propias.',
            'shared_with_user_ids.required' => 'Seleccioná al menos un usuario para compartir.',
            'shared_with_user_ids.min' => 'Seleccioná al menos un usuario para compartir.',
            'shared_with_user_ids.*.exists' => 'Uno de los usuarios seleccionados no existe.',
            'shared_with_user_ids.*.not_in' => 'No podés compartir una contraseña con vos mismo.',
        ];
    }
}
