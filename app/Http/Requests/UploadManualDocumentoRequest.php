<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadManualDocumentoRequest extends FormRequest
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'docx', 'md', 'html'];

    private const MAX_SIZE_KB = 51200;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $permission = match ($this->input('tipo')) {
            'cecoco' => 'cargar-manuales-cecoco',
            'instructivo' => 'cargar-instructivos',
            default => null,
        };

        if ($permission !== null) {
            return $this->user()?->can($permission) ?? false;
        }

        return ($this->user()?->can('cargar-manuales-cecoco') ?? false)
            || ($this->user()?->can('cargar-instructivos') ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['cecoco', 'instructivo'])],
            'titulo' => ['nullable', 'required_if:tipo,instructivo', 'string', 'max:255'],
            'tematica' => ['nullable', 'required_if:tipo,instructivo', 'string', 'max:100'],
            'archivos' => ['required', 'array', 'min:1'],
            'archivos.*' => [
                'required',
                'file',
                'max:' . self::MAX_SIZE_KB,
                function (string $attribute, mixed $value, callable $fail): void {
                    $extension = strtolower($value->getClientOriginalExtension());

                    if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                        $fail('Solo se permiten archivos: ' . implode(', ', self::ALLOWED_EXTENSIONS));
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
            'titulo.required_if' => 'El título es obligatorio para los instructivos.',
            'tematica.required_if' => 'La temática es obligatoria para los instructivos.',
            'archivos.required' => 'Seleccioná al menos un archivo.',
            'archivos.*.max' => 'Cada archivo puede pesar hasta 50 MB.',
        ];
    }
}
