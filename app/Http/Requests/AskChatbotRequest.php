<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AskChatbotRequest extends FormRequest
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
            'question' => ['required', 'string', 'max:1000'],
            'context_path' => ['nullable', 'string', 'max:255', 'regex:/^\/[A-Za-z0-9\/_\-.]*$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.required' => 'Escribí una consulta para el asistente.',
            'question.max' => 'La consulta no puede superar los 1000 caracteres.',
            'context_path.regex' => 'La pantalla actual no tiene un formato válido.',
        ];
    }
}
