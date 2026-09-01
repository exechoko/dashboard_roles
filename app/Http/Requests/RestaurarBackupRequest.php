<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * La restauración pisa la base de datos en vivo: exige que el usuario tipee
 * el nombre exacto del archivo como confirmación (no alcanza con un checkbox).
 */
class RestaurarBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('restaurar-configuracion-backup') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirmacion' => 'required|string',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $archivo = $this->route('archivo');
            if ($this->input('confirmacion') !== $archivo) {
                $validator->errors()->add('confirmacion', 'El nombre ingresado no coincide con el del backup a restaurar.');
            }
        });
    }
}
