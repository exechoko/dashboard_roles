<?php

namespace App\Services\Descargas;

use App\Mail\NuevoArchivoDescargaMail;
use App\Models\DescargaArchivo;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DescargaNotificador
{
    public function notificarNuevoArchivo(DescargaArchivo $archivo): void
    {
        if (!config('descargas.notificar_nuevos_archivos')) {
            return;
        }

        $usuarios = $this->obtenerUsuariosARootificar($archivo);

        foreach ($usuarios as $usuario) {
            try {
                Mail::to($usuario->email)->send(new NuevoArchivoDescargaMail($archivo, $usuario));
            } catch (\Exception $e) {
                Log::error('Error enviando notificación de descarga', [
                    'archivo_id' => $archivo->id,
                    'user_id' => $usuario->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function obtenerUsuariosARootificar(DescargaArchivo $archivo): \Illuminate\Support\Collection
    {
        $roleIds = $archivo->roles->pluck('id');

        return User::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('roles.id', $roleIds);
        })
            ->where('activo', true)
            ->whereNotNull('email')
            ->get();
    }
}
