<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Models\ChatConversacion;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-chat');
    }

    public function index()
    {
        return view('movil.chat.index');
    }

    public function show(ChatConversacion $conversacion)
    {
        abort_unless(
            $conversacion->participantes()->where('user_id', auth()->id())->exists(),
            404
        );

        $nombre = $conversacion->nombrePara(auth()->user());

        return view('movil.chat.show', compact('conversacion', 'nombre'));
    }
}
