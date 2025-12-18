<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TareasDelDiaMail extends Mailable
{
    use Queueable, SerializesModels;

    public Carbon $fecha;

    /** @var \Illuminate\Support\Collection */
    public Collection $items;

    public function __construct(Carbon $fecha, Collection $items)
    {
        $this->fecha = $fecha;
        $this->items = $items;
    }

    public function build()
    {
        $fechaTitulo = $this->fecha->format('d/m/Y');

        return $this
            ->subject('Tareas del día ' . $fechaTitulo)
            ->view('emails.tareas_del_dia');
    }
}
