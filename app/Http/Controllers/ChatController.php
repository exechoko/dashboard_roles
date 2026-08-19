<?php

namespace App\Http\Controllers;

use App\Events\ChatConversacionCreada;
use App\Events\ChatEscribiendo;
use App\Events\ChatLeido;
use App\Events\ChatMensajeEnviado;
use App\Http\Requests\CrearConversacionChatRequest;
use App\Http\Requests\EnviarMensajeChatRequest;
use App\Models\ChatAdjunto;
use App\Models\ChatConversacion;
use App\Models\ChatMensaje;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-chat');
    }

    public function index(): View
    {
        return view('chat.index');
    }

    public function sync(Request $request): JsonResponse
    {
        $user = $request->user();
        $conversacionActivaId = $request->integer('conversacion') ?: null;
        $desde = $request->integer('desde') ?: 0;

        Cache::put("chat.online.{$user->id}", true, now()->addSeconds(90));

        $conversaciones = $user->chatConversaciones()
            ->with([
                'ultimoMensaje.adjuntos',
                'usuarios' => fn ($query) => $query->where('users.id', '!=', $user->id)
                    ->select('users.id', 'users.name', 'users.apellido', 'users.photo'),
            ])
            ->get();

        $noLeidosPorConversacion = ChatMensaje::query()
            ->join('chat_participantes', function ($join) use ($user): void {
                $join->on('chat_participantes.chat_conversacion_id', '=', 'chat_mensajes.chat_conversacion_id')
                    ->where('chat_participantes.user_id', $user->id);
            })
            ->where('chat_mensajes.user_id', '!=', $user->id)
            ->whereRaw('chat_mensajes.id > COALESCE(chat_participantes.ultimo_leido_id, 0)')
            ->selectRaw('chat_mensajes.chat_conversacion_id as conversacion_id, COUNT(*) as total')
            ->groupBy('chat_mensajes.chat_conversacion_id')
            ->pluck('total', 'conversacion_id');

        $listaConversaciones = $conversaciones
            ->map(fn (ChatConversacion $conversacion): array => $this->conversacionData(
                $conversacion,
                (int) ($noLeidosPorConversacion[$conversacion->id] ?? 0)
            ))
            ->sortByDesc('actualizado_en')
            ->values();

        $mensajesNuevos = collect();
        $escribiendo = collect();
        $lecturas = collect();

        $conversacionActiva = $conversacionActivaId !== null
            ? $conversaciones->firstWhere('id', $conversacionActivaId)
            : null;

        if ($conversacionActiva !== null) {
            $mensajesNuevos = $conversacionActiva->mensajes()
                ->where('id', '>', $desde)
                ->with(['usuario:id,name,apellido', 'adjuntos'])
                ->orderBy('id')
                ->get()
                ->map(fn (ChatMensaje $mensaje): array => $mensaje->paraChat())
                ->values();

            $escribiendo = $conversacionActiva->participantes()
                ->where('user_id', '!=', $user->id)
                ->pluck('user_id')
                ->filter(fn (int $id): bool => Cache::has("chat.escribiendo.{$conversacionActiva->id}.{$id}"))
                ->values();

            $lecturas = $conversacionActiva->participantes()
                ->where('user_id', '!=', $user->id)
                ->pluck('ultimo_leido_id', 'user_id');
        }

        return response()->json([
            'conversaciones' => $listaConversaciones,
            'no_leidos_total' => (int) $noLeidosPorConversacion->sum(),
            'mensajes' => $mensajesNuevos,
            'escribiendo' => $escribiendo,
            'lecturas' => $lecturas,
        ]);
    }

    public function conversacion(Request $request, ChatConversacion $conversacion): JsonResponse
    {
        $this->autorizarParticipante($request, $conversacion);

        $antes = $request->integer('antes') ?: null;

        $mensajes = $conversacion->mensajes()
            ->when($antes, fn ($query) => $query->where('id', '<', $antes))
            ->with(['usuario:id,name,apellido', 'adjuntos'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->sortBy('id')
            ->values()
            ->map(fn (ChatMensaje $mensaje): array => $mensaje->paraChat())
            ->values();

        $lecturas = $conversacion->participantes()
            ->where('user_id', '!=', $request->user()->id)
            ->pluck('ultimo_leido_id', 'user_id');

        return response()->json(['mensajes' => $mensajes, 'lecturas' => $lecturas]);
    }

    public function contactos(Request $request): JsonResponse
    {
        $usuarios = User::query()
            ->where('id', '!=', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'apellido', 'photo'])
            ->map(fn (User $usuario): array => [
                'id' => $usuario->id,
                'nombre' => trim($usuario->name . ' ' . $usuario->apellido),
                'foto' => $usuario->photo ? asset($usuario->photo) : null,
                'en_linea' => $this->estaEnLinea($usuario->id),
            ])
            ->values();

        return response()->json(['usuarios' => $usuarios]);
    }

    public function iniciar(CrearConversacionChatRequest $request): JsonResponse
    {
        $user = $request->user();

        $destinatarios = collect($request->input('usuarios'))
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => $id === $user->id)
            ->values();

        if ($request->input('tipo') === 'privada') {
            $otroId = $destinatarios->first();

            $conversacion = ChatConversacion::query()
                ->where('tipo', 'privada')
                ->whereHas('participantes', fn ($query) => $query->where('user_id', $user->id))
                ->whereHas('participantes', fn ($query) => $query->where('user_id', $otroId))
                ->withCount('participantes')
                ->having('participantes_count', 2)
                ->first();

            if ($conversacion === null) {
                $conversacion = DB::transaction(function () use ($user, $otroId): ChatConversacion {
                    $conversacion = ChatConversacion::create([
                        'tipo' => 'privada',
                        'creado_por' => $user->id,
                    ]);

                    $conversacion->participantes()->createMany([
                        ['user_id' => $user->id],
                        ['user_id' => $otroId],
                    ]);

                    return $conversacion;
                });
            }
        } else {
            $conversacion = DB::transaction(function () use ($request, $user, $destinatarios): ChatConversacion {
                $conversacion = ChatConversacion::create([
                    'tipo' => 'grupo',
                    'nombre' => $request->input('nombre'),
                    'creado_por' => $user->id,
                ]);

                $conversacion->participantes()->create(['user_id' => $user->id, 'es_admin' => true]);

                foreach ($destinatarios as $id) {
                    $conversacion->participantes()->create(['user_id' => $id]);
                }

                return $conversacion;
            });
        }

        $conversacionCreada = $conversacion->wasRecentlyCreated;

        $conversacion->load([
            'ultimoMensaje',
            'usuarios' => fn ($query) => $query->where('users.id', '!=', $user->id)
                ->select('users.id', 'users.name', 'users.apellido', 'users.photo'),
        ]);

        if ($conversacionCreada) {
            ChatConversacionCreada::dispatch($conversacion);
        }

        return response()->json(['conversacion' => $this->conversacionData($conversacion)], 201);
    }

    public function enviar(EnviarMensajeChatRequest $request, ChatConversacion $conversacion): JsonResponse
    {
        $this->autorizarParticipante($request, $conversacion);

        $mensaje = DB::transaction(function () use ($request, $conversacion): ChatMensaje {
            $mensaje = $conversacion->mensajes()->create([
                'user_id' => $request->user()->id,
                'cuerpo' => $request->input('cuerpo'),
            ]);

            foreach ($request->file('adjuntos', []) as $archivo) {
                $ruta = $archivo->storeAs(
                    "chat/{$conversacion->id}",
                    Str::uuid() . '.' . $archivo->getClientOriginalExtension(),
                    'anexos'
                );

                $mensaje->adjuntos()->create([
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'ruta' => $ruta,
                    'mime' => $archivo->getClientMimeType(),
                    'tamano' => $archivo->getSize(),
                ]);
            }

            $conversacion->touch();

            $conversacion->participantes()
                ->where('user_id', $request->user()->id)
                ->update(['ultimo_leido_id' => $mensaje->id, 'ultimo_leido_at' => now()]);

            return $mensaje;
        });

        Cache::forget("chat.escribiendo.{$conversacion->id}.{$request->user()->id}");

        $mensaje->load(['usuario:id,name,apellido', 'adjuntos']);

        ChatMensajeEnviado::dispatch($mensaje, $conversacion);

        return response()->json(['mensaje' => $mensaje->paraChat()], 201);
    }

    public function marcarLeido(Request $request, ChatConversacion $conversacion): JsonResponse
    {
        $this->autorizarParticipante($request, $conversacion);

        $ultimoMensajeId = $conversacion->mensajes()->max('id');

        $conversacion->participantes()
            ->where('user_id', $request->user()->id)
            ->update(['ultimo_leido_id' => $ultimoMensajeId, 'ultimo_leido_at' => now()]);

        ChatLeido::dispatch($conversacion->id, $request->user()->id, (int) $ultimoMensajeId);

        return response()->json(['success' => true]);
    }

    public function escribiendo(Request $request, ChatConversacion $conversacion): JsonResponse
    {
        $this->autorizarParticipante($request, $conversacion);

        Cache::put("chat.escribiendo.{$conversacion->id}.{$request->user()->id}", true, now()->addSeconds(6));

        ChatEscribiendo::dispatch($conversacion->id, $request->user()->id);

        return response()->json(['success' => true]);
    }

    public function adjunto(Request $request, ChatAdjunto $adjunto): BinaryFileResponse
    {
        $this->autorizarParticipante($request, $adjunto->mensaje->conversacion);

        abort_unless(Storage::disk('anexos')->exists($adjunto->ruta), 404);

        return response()->file(Storage::disk('anexos')->path($adjunto->ruta), [
            'Content-Type' => $adjunto->mime,
        ]);
    }

    protected function autorizarParticipante(Request $request, ChatConversacion $conversacion): void
    {
        abort_unless(
            $conversacion->participantes()->where('user_id', $request->user()->id)->exists(),
            404
        );
    }

    protected function estaEnLinea(int $userId): bool
    {
        return Cache::has("chat.online.{$userId}");
    }

    /**
     * @return array{id: int, tipo: string, nombre: string, foto: string|null, ultimo_mensaje: string|null, actualizado_en: string|null, no_leidos: int, en_linea: bool, en_linea_count: int, participantes_count: int, participantes_ids: array<int, int>}
     */
    protected function conversacionData(ChatConversacion $conversacion, int $noLeidos = 0): array
    {
        $otro = $conversacion->tipo === 'privada' ? $conversacion->usuarios->first() : null;
        $enLineaCount = $conversacion->usuarios->filter(fn (User $u): bool => $this->estaEnLinea($u->id))->count();

        return [
            'id' => $conversacion->id,
            'tipo' => $conversacion->tipo,
            'nombre' => $conversacion->tipo === 'grupo'
                ? $conversacion->nombre
                : ($otro !== null ? trim($otro->name . ' ' . $otro->apellido) : 'Usuario eliminado'),
            'foto' => ($otro !== null && $otro->photo) ? asset($otro->photo) : null,
            'ultimo_mensaje' => $conversacion->ultimoMensaje?->cuerpo
                ?? ($conversacion->ultimoMensaje?->adjuntos->isNotEmpty() ? 'Adjunto' : null),
            'actualizado_en' => ($conversacion->ultimoMensaje->created_at ?? $conversacion->updated_at)?->toIso8601String(),
            'no_leidos' => $noLeidos,
            'en_linea' => $otro !== null ? $this->estaEnLinea($otro->id) : false,
            'en_linea_count' => $enLineaCount,
            'participantes_count' => $conversacion->usuarios->count() + 1,
            // Ids de los demás participantes (sin incluirme). El frontend los usa para
            // recalcular "en línea" en vivo con la presencia de Ably, sin volver a pedir al servidor.
            'participantes_ids' => $conversacion->usuarios->pluck('id')->values()->all(),
        ];
    }
}
