<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\MailBuzon;
use App\Models\User;
use App\Services\Chatbot\ConsultaDatos;

class BuzonesDisponiblesConsulta extends ConsultaDatos
{
    public function nombre(): string
    {
        return 'buzones_disponibles';
    }

    public function descripcion(): string
    {
        return 'Lista de los buzones de correo que el usuario puede consultar, con la cantidad de mensajes indexados de cada uno.';
    }

    public function permisos(): array
    {
        return ['ver-visor-mails', 'administrar-visor-mails'];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $buzones = MailBuzon::query()
            ->accesiblesPor($usuario)
            ->withCount('mensajes')
            ->orderBy('nombre')
            ->get();

        if ($buzones->isEmpty()) {
            return 'No tenés ningún buzón de correo habilitado según tus roles actuales.';
        }

        $lineas = $buzones
            ->map(function (MailBuzon $buzon): string {
                $etiqueta = $buzon->nombre . ($buzon->email ? ' (' . $buzon->email . ')' : '');
                $estado = $buzon->activo ? '' : ' — inactivo';

                return '- ' . $etiqueta . ': '
                    . $this->pluralizar((int) $buzon->mensajes_count, 'mensaje', 'mensajes') . $estado;
            })
            ->implode("\n");

        return 'Tenés acceso a ' . $this->pluralizar($buzones->count(), 'buzón', 'buzones') . ":\n"
            . $lineas
            . "\n\nDetalle en [Visor de correos](/herramientas/mails).";
    }
}
