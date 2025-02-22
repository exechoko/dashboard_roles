<?php

namespace App\Models;

use Doctrine\Common\Cache\Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache as FacadesCache;
use Illuminate\Support\Str;

class Destino extends Model
{
    protected $table = 'destino';

    public function direccion()
    {
        return $this->belongsTo(Direccion::class);
    }

    public function departamental()
    {
        return $this->belongsTo(Departamental::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function destacamento()
    {
        return $this->belongsTo(Destacamento::class);
    }

    public function comisaria()
    {
        return $this->belongsTo(Comisaria::class);
    }

    public function seccion()
    {
        return $this->belongsTo(Seccion::class);
    }

    public function empresa_soporte()
    {
        return $this->belongsTo(EmpresaSoporte::class);
    }

    public function recurso()
    {
        return $this->hasMany(Recurso::class);
    }

    public function flota_general()
    {
        return $this->hasMany(FlotaGeneral::class);
    }

    public function historico()
    {
        return $this->hasMany(Historico::class);
    }

    public function camara()
    {
        return $this->hasMany(Camara::class);
    }

    public function sitio()
    {
        return $this->hasMany(Sitio::class);
    }

    public function dependeDe()
    {
        if (Str::contains($this->nombre, 'Direcc')) {
            return 'Jefatura Policia de Entre Ríos';
        }
        if (Str::contains($this->nombre, 'Departamental') && !is_null($this->direccion_id)) {
            return $this->direccion->nombre;
        }
        if (Str::contains($this->nombre, 'Divis')) {
            $depende = [];
            // Primero agregamos la Departamental si existe
            if (!is_null($this->departamental_id)) {
                $depende[] = $this->departamental->nombre;
            }
            // Luego agregamos la Dirección si existe
            if (!is_null($this->direccion_id)) {
                $depende[] = $this->direccion->nombre;
            }
            return implode(' - ', $depende);
        }
        if (Str::contains($this->nombre, 'Comisar')) {
            $depende = [];
            // Primero agregamos la Departamental si existe
            if (!is_null($this->departamental_id)) {
                $depende[] = $this->departamental->nombre;
            }
            // Luego agregamos la Dirección si existe
            if (!is_null($this->direccion_id)) {
                $depende[] = $this->direccion->nombre;
            }
            return implode(' - ', $depende);
        }
        if (Str::contains($this->nombre, 'Secci')) {
            $depende = [];
            //Primero Comisaria
            if (!is_null($this->comisaria_id)) {
                $depende[] = $this->comisaria->nombre;
            }
            // Al mismmo nivel la División
            if (!is_null($this->division_id)) {
                $depende[] = $this->division->nombre;
            }
            // Luego agregamos la Departamental si existe
            if (!is_null($this->departamental_id)) {
                $depende[] = $this->departamental->nombre;
            }
            // Luego agregamos la Dirección si existe
            if (!is_null($this->direccion_id)) {
                $depende[] = $this->direccion->nombre;
            }
            return implode(' - ', $depende);
        }
    }


    public function destinosDependientes($categoria, $destinoId)
    {
        // Mapeo de categorías a columnas
        $columnas = [
            'direccion'     => 'direccion_id',
            'departamental' => 'departamental_id',
            'division'      => 'division_id',
            'comisaria'     => 'comisaria_id',
            'seccion'       => 'seccion_id'
        ];

        // Verificar si la categoría es válida
        if (!array_key_exists($categoria, $columnas)) {
            return collect(); // o throw new \InvalidArgumentException("Categoría no válida");
        }

        $columna = $columnas[$categoria];
        $dependencia_id = Destino::where('id', $destinoId)->pluck($columna)->first();
        $destinos = Destino::where($columna, $dependencia_id)
            ->pluck('id')
            ->unique()
            ->values();
        //dd($destinos);

        // Buscar todos los destinos que pertenecen a la categoría padre
        return $destinos;
    }

    public function auditoria()
    {
        return $this->hasMany(Auditoria::class);
    }
}
