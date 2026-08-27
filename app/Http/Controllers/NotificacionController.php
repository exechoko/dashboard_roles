<?php

namespace App\Http\Controllers;

use App\Models\DispositivoEdificio;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    private const POR_PAGINA = 30;

    /**
     * Íconos para los orígenes de notificación que no son un tipo de
     * dispositivos_edificio (ver DispositivoEdificio::getTiposDispositivos()
     * para pc/servidor/camara_interna/router/switch/etc).
     */
    private const ICONOS_EXTRA = [
        'camara_cctv' => 'fas fa-video',
        'pc_video' => 'fas fa-desktop',
        'troncal_telefonica' => 'fas fa-phone-volume',
    ];

    public function __construct()
    {
        $this->middleware('permission:ver-infraestructura-notificaciones');
    }

    public function sync(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $notificaciones = Notificacion::query()
            ->deInfraestructura()
            ->latest('created_at')
            ->limit(self::POR_PAGINA)
            ->get();

        $vistasEn = $usuario->notificaciones_vistas_en;
        $noLeidas = Notificacion::query()
            ->deInfraestructura()
            ->noLeidasDesde($vistasEn)
            ->count();

        return response()->json([
            'notificaciones' => $notificaciones->map(fn (Notificacion $n): array => $this->notificacionData($n, $vistasEn)),
            'no_leidas_total' => $noLeidas,
        ]);
    }

    public function marcarLeidas(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $usuario->notificaciones_vistas_en = now();
        $usuario->save();

        return response()->json(['ok' => true]);
    }

    public function vaciar(): JsonResponse
    {
        Notificacion::query()->deInfraestructura()->delete();

        return response()->json(['ok' => true]);
    }

    private function notificacionData(Notificacion $notificacion, ?string $vistasEn): array
    {
        return [
            'id' => $notificacion->id,
            'tipo' => $notificacion->tipo,
            'nivel' => $notificacion->nivel,
            'titulo' => $notificacion->titulo,
            'mensaje' => $notificacion->mensaje,
            'icono' => $this->iconoDispositivo($notificacion),
            'creado_en' => $notificacion->created_at->toDateTimeString(),
            'creado_en_humano' => $notificacion->created_at->diffForHumans(),
            'leida' => $vistasEn ? $notificacion->created_at->lte($vistasEn) : false,
        ];
    }

    /**
     * Ícono según el tipo de dispositivo/origen que generó la notificación
     * (guardado en datos.tipo), para distinguir de un vistazo si se trata de
     * una cámara, una PC, un servidor, un troncal telefónico, etc.
     */
    private function iconoDispositivo(Notificacion $notificacion): string
    {
        $tipo = $notificacion->datos['tipo'] ?? null;

        if ($tipo === null) {
            return 'fas fa-bell';
        }

        return DispositivoEdificio::getTiposDispositivos()[$tipo]['icon']
            ?? self::ICONOS_EXTRA[$tipo]
            ?? 'fas fa-bell';
    }
}
