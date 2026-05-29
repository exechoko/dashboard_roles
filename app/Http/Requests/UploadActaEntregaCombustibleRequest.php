<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadActaEntregaCombustibleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'acta_firmada' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'acta_firmada.required' => 'Debe cargar el acta de entrega firmada.',
            'acta_firmada.mimes' => 'El acta firmada debe ser una imagen, PDF o documento Word.',
            'acta_firmada.max' => 'El acta firmada no debe superar los 8 MB.',
        ];
    }
}
