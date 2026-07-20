<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CredencialesAccesoMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $nombreUsuario;

    public string $sistemaNombre;

    public string $sistemaURL;

    public string $emailUsuario;

    public function __construct(string $nombreUsuario, string $emailUsuario)
    {
        $this->nombreUsuario = $nombreUsuario;
        $this->emailUsuario = $emailUsuario;
        $this->sistemaNombre = 'Sistema CAR911';
        $this->sistemaURL = config('app.url');
    }

    public function build()
    {
        return $this
            ->subject('Credenciales de Acceso - Sistema CAR911')
            ->view('emails.credenciales_acceso');
    }
}
