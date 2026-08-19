<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MensajesChatSinLeerMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var \Illuminate\Support\Collection */
    public Collection $pendientes;

    public function __construct(Collection $pendientes)
    {
        $this->pendientes = $pendientes;
    }

    public function build()
    {
        return $this
            ->subject('Tenés mensajes sin leer en el chat interno')
            ->view('emails.chat_mensajes_sin_leer');
    }
}
