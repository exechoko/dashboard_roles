<?php

namespace App\Http\Controllers;

use App\Imports\CamaraImport;
use App\Exports\CamarasExport;
use App\Models\Camara;
use App\Models\Destino;
use App\Models\Sitio;
use App\Models\TipoCamara;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;

class CamaraController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-camara|crear-camara|editar-camara|borrar-camara')->only('index');
        $this->middleware('permission:crear-camara', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-camara', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-camara', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $texto = trim($request->get('texto')); //trim quita espacios vacios
        $camaras = Camara::where('ip', 'LIKE', '%' . $texto . '%')
            ->orWhere('nombre', 'LIKE', '%' . $texto . '%')
            ->orWhereHas('sitio', function ($query) use ($texto) {
                $query->where('nombre', 'LIKE', '%' . $texto . '%');
            })
            ->orWhereHas('tipoCamara', function ($query) use ($texto) {
                $query->where('tipo', 'LIKE', '%' . $texto . '%');
            })
            ->orderBy('id', 'asc')
            ->paginate(100);

        $fijas = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', 'Fija');
        })
            ->whereHas('sitio', function ($query) {
                $query->where('activo', 1);
            })
            ->count();

        $fijasFR = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', 'Fija - FR');
        })
            ->whereHas('sitio', function ($query) {
                $query->where('activo', 1);
            })
            ->count();

        $fijasLPR = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', 'Fija - LPR')
                ->orWhere('tipo', 'Fija - LPR NV')
                ->orWhere('tipo', 'Fija - LPR AV');
        })
            ->whereHas('sitio', function ($query) {
                $query->where('activo', 1);
            })
            ->count();

        $domos = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', 'Domo');
        })
            ->whereHas('sitio', function ($query) {
                $query->where('activo', 1);
            })
            ->count();

        $domosDuales = Camara::whereHas('tipoCamara', function ($query) {
            $query->where('tipo', 'Domo Dual');
        })
            ->whereHas('sitio', function ($query) {
                $query->where('activo', 1);
            })
            ->count();
        $totalCam = Camara::select('camaras.id')
            ->leftJoin('sitio', 'camaras.sitio_id', '=', 'sitio.id')
            ->where('sitio.activo', 1)
            ->count();

        $totalCamaras = Camara::select(
            'camaras.id',
            'tipo_camara.canales as canales'
        )
            ->leftJoin('sitio', 'camaras.sitio_id', '=', 'sitio.id')
            ->leftJoin('tipo_camara', 'camaras.tipo_camara_id', '=', 'tipo_camara.id')
            ->where('sitio.activo', 1)
            ->get();

        $cantidadCanales = 0;
        foreach ($totalCamaras as $camara) {
            $cantidadCanales += $camara->canales;
        }

        /*return view('mapa.mapa',
            [
                'comisarias' => $comisarias,
                'antenas' => $antenas,
                'camaras' => $camaras,
                'jurisdicciones' => $jurisdicciones,
                'fijas' => $fijas,
                'fijasFR' => $fijasFR, 'fijasLPR' => $fijasLPR,
                'domos' => $domos,
                'domosDuales' => $domosDuales,
                'total' => $totalCam
            ]
        );
*/

        //$camaras = Equipo::paginate(5);
        return view('camaras.index', compact(
            'camaras',
            'cantidadCanales',
            'texto',
            'fijas',
            'fijasFR',
            'fijasLPR',
            'domos',
            'domosDuales',
            'totalCam'
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tipoCamara = TipoCamara::all();
        $dependencias = Destino::all();
        $sitios = Sitio::where('activo', 1)->get();
        return view('camaras.crear', compact('tipoCamara', 'dependencias', 'sitios'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate([
            'tipo_camara_id' => 'required|not_in:Selecciona un tipo de cámara',
            'sitio_id' => 'required|not_in:Seleccionar',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);
        //dd($request->all());
        try {
            DB::beginTransaction();
            $s = Sitio::find($request->sitio_id);
            $camara = new Camara;
            $camara->ip = $request->ip;
            $camara->tipo_camara_id = $request->tipo_camara_id;
            $camara->nombre = $request->nombre;
            $camara->sitio_id = $request->sitio_id;
            $camara->latitud = (string) $s->latitud;
            $camara->longitud = (string) $s->longitud;
            $camara->inteligencia = $request->inteligencia;
            $camara->nro_serie = $request->nro_serie;
            $camara->fecha_instalacion = $request->fecha_instalacion;
            $camara->etapa = $request->etapa;
            $camara->observaciones = $request->observaciones;
            $camara->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
            ]);
        }

        return redirect()->route('camaras.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $camara = Camara::find($id);
        $tipoCamara = TipoCamara::all();
        $dependencias = Destino::all();
        $sitios = Sitio::all();
        return view('camaras.editar', compact('camara', 'tipoCamara', 'dependencias', 'sitios'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //dd($request->all());
        request()->validate([
            'sitio_id' => 'required|not_in:Seleccionar',
            'tipo_camara_id' => 'required|not_in:Selecciona un tipo de cámara',
        ], [
            'required' => 'El campo :attribute es necesario completar.'
        ]);
        try {
            DB::beginTransaction();
            $camara = Camara::find($id);
            $s = Sitio::find($request->sitio_id);

            if (!is_null($camara)) {
                $camara->ip = $request->ip;
                $camara->tipo_camara_id = $request->tipo_camara_id;
                $camara->nombre = $request->nombre;
                $camara->sitio_id = $request->sitio_id;
                $camara->latitud = (string) $s->latitud;
                $camara->longitud = (string) $s->longitud;
                $camara->inteligencia = $request->inteligencia;
                $camara->nro_serie = $request->nro_serie;
                $camara->fecha_instalacion = $request->fecha_instalacion;
                $camara->etapa = $request->etapa;
                $camara->observaciones = $request->observaciones;

                $camara->save();
                DB::commit();
            } else {
                return redirect()->back()->with('error', 'Error al actualizar cámara.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'result' => 'ERROR',
                'message' => $e->getMessage()
            ]);
        }
        return redirect()->route('camaras.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $camara = Camara::find($id);
        $camara->delete();
        return redirect()->route('camaras.index');
    }

    public function reiniciar($id)
    {
        $camara = Camara::findOrFail($id);
        $ip = $camara->ip;

        $user = env('CAMARA_USER');
        $pass = env('CAMARA_PASS');

        $url = "http://{$user}:{$pass}@{$ip}/cgi-bin/magicBox.cgi?action=reboot";

        // Retornar con la URL para abrir en nueva pestaña
        return back()->with([
            'success' => 'Abriendo pestaña para reiniciar cámara...',
            'open_url' => $url
        ]);
    }

    public function importExcel(Request $request)
    {
        $file = $request->file('excel_file');
        Excel::import(new CamaraImport, $file);
        /*Excel::import($file, function($reader){
            foreach ($reader->get() as $camara) {
                Camara::create([
                    'nombre' => $camara->nombre,
                    'ip' =>$camara->ip,
                    'tipo' =>$camara->tipo,
                    'inteligencia' =>$camara->inteligencia,
                    'marca' =>$camara->marca,
                    'modelo' =>$camara->modelo,
                    'nro_serie' =>$camara->nro_serie,
                    'etapa' =>$camara->etapa,
                    'sitio' =>$camara->sitio,
                    'latitud' =>$camara->latitud,
                    'longitud' =>$camara->longitud,
                ]);
            }
        });*/

        return redirect()->back()->with('success', 'Los datos se han importado correctamente');
    }

    public function exportExcel()
    {
        return Excel::download(new CamarasExport, 'ListadoCamaras_' . Carbon::now() . '.xlsx');
    }
}
