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
            ->whereNotNull('email')
            ->get();
    }

    /**
     * Notificar a un usuario específico que se le dio acceso directo a un archivo
     */
    public function notificarAccesoDirecto(DescargaArchivo $archivo, User $usuario): void
    {
        if (!$usuario->email) {
            Log::warning('No se puede notificar al usuario sin email', [
                'archivo_id' => $archivo->id,
                'user_id' => $usuario->id,
            ]);
            return;
        }

        try {
            Mail::to($usuario->email)->send(new NuevoArchivoDescargaMail($archivo, $usuario));
            
            Log::info('Notificación de acceso directo enviada', [
                'archivo_id' => $archivo->id,
                'user_id' => $usuario->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error enviando notificación de acceso directo', [
                'archivo_id' => $archivo->id,
                'user_id' => $usuario->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
