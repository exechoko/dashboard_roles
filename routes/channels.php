<?php

use App\Models\ChatConversacion;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.usuario.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

Broadcast::channel('chat.conversacion.{id}', function (User $user, int $id): bool {
    return ChatConversacion::query()
        ->whereKey($id)
        ->whereHas('participantes', fn ($query) => $query->where('user_id', $user->id))
        ->exists();
});

Broadcast::channel('chat.presencia', function (User $user): ?array {
    return $user->can('ver-chat')
        ? ['id' => $user->id, 'nombre' => trim($user->name . ' ' . $user->apellido)]
        : null;
});
