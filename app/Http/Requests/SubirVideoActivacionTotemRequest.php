<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubirVideoActivacionTotemRequest extends FormRequest
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
            'camara_id' => 'required|exists:camaras,id',
            'video' => 'required|file|mimes:mp4,avi,mov,mkv,wmv,asf,mpeg,mpg|max:184320',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'camara_id.required' => 'Debe seleccionar el tótem correspondiente.',
            'video.required' => 'Debe seleccionar el archivo de video.',
            'video.mimes' => 'Formatos permitidos: MP4, AVI, MOV, MKV, WMV, ASF, MPEG.',
            'video.max' => 'El video no debe superar los 180 MB.',
        ];
    }
}
