<?php

namespace App\Http\Controllers;

use App\Models\Sitio;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class SitioController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-sitio|crear-sitio|editar-sitio|borrar-sitio')->only('index');
        $this->middleware('permission:crear-sitio', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-sitio', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-sitio', ['only' => ['destroy']]);
    }

    public function index()
    {
        $sitios = Sitio::paginate(50);
        return view('sitio.index', compact('sitios'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sitio  $sitio
     * @return \Illuminate\Http\Response
     */
    public function show(Sitio $sitio)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Sitio  $sitio
     * @return \Illuminate\Http\Response
     */
    public function edit(Sitio $sitio)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sitio  $sitio
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sitio $sitio)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sitio  $sitio
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sitio $sitio)
    {
        //
    }
}
