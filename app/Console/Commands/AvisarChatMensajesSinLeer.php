<?php

namespace App\Console\Commands;

use App\Mail\MensajesChatSinLeerMail;
use App\Models\ChatConversacion;
use App\Models\ChatMensaje;
use App\Models\ChatParticipante;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AvisarChatMensajesSinLeer extends Command
{
    protected $signature = 'chat:avisar-no-leidos {--minutos=30 : Antigüedad mínima del mensaje sin leer para avisar}';

    protected $description = 'Avisa por mail a los usuarios que tienen mensajes de chat sin leer hace más de N minutos.';

    public function handle(): int
    {
        $umbral = now()->subMinutes((int) $this->option('minutos'));

        $pendientes = ChatMensaje::query()
            ->join('chat_participantes', function ($join): void {
                $join->on('chat_participantes.chat_conversacion_id', '=', 'chat_mensajes.chat_conversacion_id')
                    ->whereColumn('chat_mensajes.user_id', '!=', 'chat_participantes.user_id');
            })
            ->whereRaw('chat_mensajes.id > COALESCE(chat_participantes.ultimo_leido_id, 0)')
            ->whereNull('chat_participantes.aviso_no_leido_enviado_at')
            ->groupBy('chat_participantes.id', 'chat_participantes.user_id', 'chat_participantes.chat_conversacion_id')
            ->havingRaw('MIN(chat_mensajes.created_at) <= ?', [$umbral])
            ->selectRaw(
                'chat_participantes.id as participante_id, '
                . 'chat_participantes.user_id as user_id, '
                . 'chat_participantes.chat_conversacion_id as conversacion_id, '
                . 'COUNT(*) as no_leidos, '
                . 'MIN(chat_mensajes.created_at) as desde'
            )
            ->get();

        if ($pendientes->isEmpty()) {
            $this->info('Sin mensajes de chat pendientes de aviso.');

            return Command::SUCCESS;
        }

        $usuarios = User::query()->whereIn('id', $pendientes->pluck('user_id')->unique())->get()->keyBy('id');
        $conversaciones = ChatConversacion::query()
            ->whereIn('id', $pendientes->pluck('conversacion_id')->unique())
            ->get()
            ->keyBy('id');

        $enviados = 0;

        foreach ($pendientes->groupBy('user_id') as $userId => $items) {
            $usuario = $usuarios->get($userId);

            if ($usuario === null || !$usuario->email) {
                continue;
            }

            $detalle = $items->map(function ($item) use ($usuario, $conversaciones) {
                $conversacion = $conversaciones->get($item->conversacion_id);

                return [
                    'conversacion_id' => $item->conversacion_id,
                    'nombre' => $conversacion?->nombrePara($usuario) ?? 'Conversación eliminada',
                    'no_leidos' => (int) $item->no_leidos,
                    'desde' => Carbon::parse($item->desde),
                ];
            });

            try {
                Mail::to($usuario->email)->send(new MensajesChatSinLeerMail($detalle));

                ChatParticipante::query()
                    ->whereIn('id', $items->pluck('participante_id'))
                    ->update(['aviso_no_leido_enviado_at' => now()]);

                $enviados++;
            } catch (\Exception $e) {
                $this->error('Error enviando a ' . $usuario->email . ': ' . $e->getMessage());
                Log::channel('daily')->error('[chat:avisar-no-leidos] Error enviando a ' . $usuario->email, [
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Avisos enviados: {$enviados}");

        return Command::SUCCESS;
    }
}
