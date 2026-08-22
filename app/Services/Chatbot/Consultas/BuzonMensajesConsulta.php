<?php

namespace App\Services\Chatbot\Consultas;

use App\Models\MailBuzon;
use App\Models\MailMensaje;
use App\Models\User;
use App\Services\Chatbot\ConsultaDatos;
use Illuminate\Contracts\Database\Query\Builder;

class BuzonMensajesConsulta extends ConsultaDatos
{
    public function nombre(): string
    {
        return 'buzon_mensajes';
    }

    public function descripcion(): string
    {
        return 'Cantidad de mensajes de un buzón de correo y fecha del último correo recibido, con el desglose por carpeta.';
    }

    public function permisos(): array
    {
        return ['ver-visor-mails', 'administrar-visor-mails'];
    }

    public function parametros(): array
    {
        return [
            'buzon' => 'Nombre o dirección de correo del buzón a consultar.',
        ];
    }

    public function ejecutar(User $usuario, array $parametros): string
    {
        $nombre = $this->texto($parametros, 'buzon');

        if ($nombre === null) {
            return 'Necesito el nombre del buzón. Preguntame por los buzones disponibles si no lo recordás.';
        }

        $patron = '%' . mb_strtolower($nombre) . '%';

        $buzon = MailBuzon::query()
            ->accesiblesPor($usuario)
            ->where(fn (Builder $query) => $query
                ->whereRaw('LOWER(nombre) LIKE ?', [$patron])
                ->orWhereRaw('LOWER(email) LIKE ?', [$patron]))
            ->first();

        if ($buzon === null) {
            return 'No encontré un buzón llamado "' . $nombre . '" entre los que podés consultar. '
                . 'Revisá el [Visor de correos](/herramientas/mails).';
        }

        $total = $buzon->mensajes()->count();

        if ($total === 0) {
            return 'El buzón ' . $buzon->nombre . ' todavía no tiene mensajes indexados. '
                . 'Detalle en [Visor de correos](/herramientas/mails).';
        }

        $ultimo = $buzon->mensajes()->orderByDesc('fecha')->first();

        $porCarpeta = $buzon->mensajes()
            ->selectRaw('carpeta, COUNT(*) AS cantidad')
            ->groupBy('carpeta')
            ->pluck('cantidad', 'carpeta')
            ->mapWithKeys(fn ($cantidad, $carpeta): array => [
                MailMensaje::CARPETAS[$carpeta] ?? ($carpeta ?: 'Sin carpeta') => (int) $cantidad,
            ])
            ->all();

        $respuesta = 'El buzón ' . $buzon->nombre . ' tiene '
            . $this->pluralizar($total, 'mensaje indexado', 'mensajes indexados') . '.';

        if ($ultimo?->fecha !== null) {
            $respuesta .= ' El último es del ' . $ultimo->fecha->format('d/m/Y H:i')
                . ($ultimo->de_email ? ', de ' . $ultimo->de_email : '')
                . ($ultimo->asunto ? ', asunto "' . $ultimo->asunto . '"' : '')
                . '.';
        }

        if ($porCarpeta !== []) {
            $respuesta .= "\n\nPor carpeta:\n" . $this->listaDeConteos($porCarpeta);
        }

        return $respuesta . "\n\nDetalle en [Visor de correos](/herramientas/mails).";
    }
}
