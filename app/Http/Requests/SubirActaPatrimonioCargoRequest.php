<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubirActaPatrimonioCargoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('firmar-patrimonio-cargos') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'acta_firmada' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'acta_firmada.required' => 'Debe seleccionar el acta firmada a subir.',
            'acta_firmada.mimes'    => 'El acta firmada debe ser un PDF o una imagen JPG, PNG o WEBP.',
            'acta_firmada.max'      => 'El acta firmada no puede superar los 10 MB.',
        ];
    }
}
