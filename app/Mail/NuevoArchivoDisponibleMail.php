<?php

namespace App\Mail;

use App\Models\DescargaArchivo;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevoArchivoDisponibleMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DescargaArchivo $archivo,
        public User $usuario
    ) {
    }

    public function build()
    {
        return $this->subject('Nuevo archivo disponible: ' . $this->archivo->nombre_original)
            ->view('emails.descargas.nuevo_archivo_disponible');
    }
}
