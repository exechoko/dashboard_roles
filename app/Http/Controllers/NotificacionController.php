<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    private const POR_PAGINA = 30;

    public function __construct()
    {
        $this->middleware('permission:ver-infraestructura-notificaciones');
    }

    public function sync(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $notificaciones = Notificacion::query()
            ->categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)
            ->latest('created_at')
            ->limit(self::POR_PAGINA)
            ->get();

        $vistasEn = $usuario->notificaciones_vistas_en;
        $noLeidas = Notificacion::query()
            ->categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)
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
        Notificacion::query()->categoria(Notificacion::CATEGORIA_INFRAESTRUCTURA)->delete();

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
            'creado_en' => $notificacion->created_at->toDateTimeString(),
            'creado_en_humano' => $notificacion->created_at->diffForHumans(),
            'leida' => $vistasEn ? $notificacion->created_at->lte($vistasEn) : false,
        ];
    }
}
