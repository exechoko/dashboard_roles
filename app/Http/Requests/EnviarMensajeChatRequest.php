<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class EnviarMensajeChatRequest extends FormRequest
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
            'cuerpo' => ['nullable', 'string', 'max:4000'],
            'adjuntos' => ['nullable', 'array', 'max:5'],
            'adjuntos.*' => [
                'file',
                'max:25600',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,mp4,mov,webm,mp3,wav,ogg,m4a',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cuerpo.max' => 'El mensaje no puede superar los 4000 caracteres.',
            'adjuntos.max' => 'Podés adjuntar hasta 5 archivos por mensaje.',
            'adjuntos.*.max' => 'Cada archivo no puede superar los 25 MB.',
            'adjuntos.*.mimes' => 'Formato de archivo no permitido.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!$this->filled('cuerpo') && !$this->hasFile('adjuntos')) {
                $validator->errors()->add('cuerpo', 'Escribí un mensaje o adjuntá un archivo.');
            }
        });
    }
}
