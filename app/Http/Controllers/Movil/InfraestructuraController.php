<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class InfraestructuraController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-infraestructura-workers');
    }

    public function index(): View
    {
        return view('movil.infraestructura.index');
    }
}
