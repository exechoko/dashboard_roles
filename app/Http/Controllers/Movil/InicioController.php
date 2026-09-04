<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;

class InicioController extends Controller
{
    public function index()
    {
        return view('movil.inicio');
    }
}
