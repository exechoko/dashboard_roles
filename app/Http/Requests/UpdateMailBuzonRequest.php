<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailBuzonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar-visor-mails') === true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'carpeta' => ['required', 'string', 'max:150', Rule::unique('mail_buzones', 'carpeta')->ignore($this->route('buzon'))],
            'email' => 'nullable|email|max:255',
            'role_id' => 'nullable|exists:roles,id',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del buzón es obligatorio.',
            'carpeta.required' => 'La carpeta dentro de Backup_mails es obligatoria.',
            'carpeta.unique' => 'Ya existe un buzón para esa carpeta.',
            'email.email' => 'El correo debe tener un formato válido.',
            'role_id.exists' => 'El rol seleccionado no existe.',
        ];
    }
}
