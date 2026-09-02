<?php

namespace App\Jobs;

use App\Mail\NuevoArchivoDisponibleMail;
use App\Models\DescargaArchivo;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarNotificacionDescarga implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        protected DescargaArchivo $archivo
    ) {
        // Ver comentario en ProcesarArchivoDescarga sobre por qué usa la
        // conexión dedicada 'descargas' en vez de la 'database' por defecto.
        $this->onConnection('descargas')->onQueue('descargas');
    }

    public function handle(): void
    {
        try {
            Log::info('Iniciando envío de notificaciones', [
                'archivo_id' => $this->archivo->id,
                'nombre_original' => $this->archivo->nombre_original,
            ]);

            // Obtener usuarios con acceso directo
            $usuariosDirectos = $this->archivo->usuarios;

            // Obtener usuarios con acceso por rol
            $usuariosPorRol = User::whereHas('roles', function ($query) {
                $query->whereIn('roles.id', $this->archivo->roles->pluck('id'));
            })->get();

            // Combinar y eliminar duplicados
            $todosLosUsuarios = $usuariosDirectos->merge($usuariosPorRol)
                ->unique('id')
                ->filter(fn($user) => $user->email);

            Log::info('Usuarios a notificar', [
                'archivo_id' => $this->archivo->id,
                'total_usuarios' => $todosLosUsuarios->count(),
            ]);

            // Enviar emails
            foreach ($todosLosUsuarios as $usuario) {
                try {
                    Mail::to($usuario->email)->send(new NuevoArchivoDisponibleMail(
                        $this->archivo,
                        $usuario
                    ));

                    Log::info('Email enviado exitosamente', [
                        'archivo_id' => $this->archivo->id,
                        'user_id' => $usuario->id,
                        'email' => $usuario->email,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error enviando email a usuario', [
                        'archivo_id' => $this->archivo->id,
                        'user_id' => $usuario->id,
                        'email' => $usuario->email,
                        'error' => $e->getMessage(),
                    ]);
                    // Continuar con el siguiente usuario
                }
            }

            Log::info('Notificaciones completadas', [
                'archivo_id' => $this->archivo->id,
                'total_enviados' => $todosLosUsuarios->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error en Job EnviarNotificacionDescarga', [
                'archivo_id' => $this->archivo->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Exception $e): void
    {
        Log::error('Job EnviarNotificacionDescarga falló definitivamente', [
            'archivo_id' => $this->archivo->id,
            'error' => $e->getMessage(),
        ]);
    }
}
