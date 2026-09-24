<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class ConvertirAudioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('ver-conversor-audio') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * La extensión se valida "a mano" (no con la regla mimes) porque GSM no
     * es un tipo MIME que Symfony/Laravel reconozca por contenido.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'archivo' => [
                'required',
                'file',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!$value instanceof UploadedFile) {
                        return;
                    }

                    $extension = strtolower((string) $value->getClientOriginalExtension());

                    if (!in_array($extension, ['gsm', 'ogg', 'wav'], true)) {
                        $fail('El archivo debe ser GSM, OGG o WAV.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo.required' => 'No se recibió el archivo a convertir.',
            'archivo.file' => 'El archivo no pudo ser procesado.',
        ];
    }
}
