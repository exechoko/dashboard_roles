<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CrearConversacionChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:privada,grupo'],
            'nombre' => ['required_if:tipo,grupo', 'nullable', 'string', 'max:120'],
            'usuarios' => ['required', 'array', 'min:1'],
            'usuarios.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo.required' => 'Indicá si es una conversación privada o un grupo.',
            'nombre.required_if' => 'Los grupos necesitan un nombre.',
            'usuarios.required' => 'Elegí al menos un destinatario.',
            'usuarios.*.exists' => 'Uno de los usuarios seleccionados no existe.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $destinatarios = collect($this->input('usuarios', []))
                ->reject(fn ($id): bool => (int) $id === $this->user()->id);

            if ($this->input('tipo') === 'privada' && $destinatarios->count() !== 1) {
                $validator->errors()->add('usuarios', 'Una conversación privada es entre vos y otro usuario.');
            }

            if ($this->input('tipo') === 'grupo' && $destinatarios->count() < 2) {
                $validator->errors()->add('usuarios', 'Un grupo necesita al menos dos destinatarios además de vos.');
            }
        });
    }
}
