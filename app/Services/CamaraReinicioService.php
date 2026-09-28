<?php

namespace App\Services;

use App\Models\Camara;
use Illuminate\Support\Facades\Http;

class CamaraReinicioService
{
    private const TIPO_BDE = 'BDE (Totem)';

    /**
     * Reinicia la cámara pegándole al cgi de reboot del fabricante (Dahua)
     * desde el servidor, usando las credenciales que correspondan según el
     * tipo de cámara (los BDE (Tótem) usan usuario y contraseña propios).
     *
     * @return array{ok: bool, mensaje: string, status: int|null}
     */
    public function reiniciar(Camara $camara): array
    {
        $ip = $camara->ip;

        if (empty($ip)) {
            return ['ok' => false, 'mensaje' => 'La cámara no tiene IP cargada.', 'status' => null];
        }

        [$user, $pass] = $this->credenciales($camara);

        if (empty($user) || empty($pass)) {
            return ['ok' => false, 'mensaje' => 'No hay credenciales configuradas para este tipo de cámara.', 'status' => null];
        }

        try {
            $respuesta = Http::withOptions([
                'auth'            => [$user, $pass, 'digest'],
                'timeout'         => 6,
                'connect_timeout' => 3,
                'verify'          => false,
            ])->get("http://{$ip}/cgi-bin/magicBox.cgi?action=reboot");

            if ($respuesta->successful()) {
                return ['ok' => true, 'mensaje' => 'Cámara reiniciada correctamente.', 'status' => $respuesta->status()];
            }

            return ['ok' => false, 'mensaje' => "La cámara respondió HTTP {$respuesta->status()}.", 'status' => $respuesta->status()];
        } catch (\Exception $e) {
            return ['ok' => false, 'mensaje' => 'No se pudo contactar la cámara.', 'status' => null];
        }
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function credenciales(Camara $camara): array
    {
        $esBde = optional($camara->tipoCamara)->tipo === self::TIPO_BDE;

        if ($esBde) {
            return [config('services.camaras.bde_user'), config('services.camaras.bde_pass')];
        }

        return [config('services.camaras.user'), config('services.camaras.pass')];
    }
}
