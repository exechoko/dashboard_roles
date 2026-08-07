<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearActivacionTotemRequest extends FormRequest
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
            'evento_cecoco_id' => 'required|exists:evento_cecoco,id|unique:activaciones_totem,evento_cecoco_id',
            'camara_id' => 'nullable|exists:camaras,id',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'evento_cecoco_id.required' => 'Tenés que elegir un evento CECOCO.',
            'evento_cecoco_id.unique' => 'Ya existe una activación registrada para ese evento CECOCO.',
        ];
    }
}
