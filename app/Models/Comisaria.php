<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comisaria extends Model
{
    protected $table = 'comisarias';
    protected $fillable = [
        'nombre'
    ];

    public function departamental(){
        return $this->belongsTo(Departamental::class);
    }

    public function seccion(){
        return $this->hasMany(Seccion::class);
    }

    public function destacamento(){
        return $this->hasMany(Destacamento::class);
    }

    public function destino(){
        return $this->hasMany(Destino::class);
    }

    public function auditoria(){
        return $this->hasMany(Auditoria::class);
    }

    /**
     * Coordenadas fijas de las comisarías para los mapas (no tienen tabla
     * propia de ubicación).
     *
     * @return array<int, array{latitud: float, longitud: float, titulo: string, numero: int}>
     */
    public static function coordenadasFijas(): array
    {
        return [
            ['latitud' => -31.72978, 'longitud' => -60.53547, 'titulo' => 'Cria. 1°', 'numero' => 1],
            ['latitud' => -31.73735, 'longitud' => -60.5284, 'titulo' => 'Cria. 2°', 'numero' => 2],
            ['latitud' => -31.757298, 'longitud' => -60.495857, 'titulo' => 'Cria. 3°', 'numero' => 3],
            ['latitud' => -31.73771, 'longitud' => -60.51383, 'titulo' => 'Cria. 4°', 'numero' => 4],
            ['latitud' => -31.73001, 'longitud' => -60.54851, 'titulo' => 'Cria. 5°', 'numero' => 5],
            ['latitud' => -31.74674, 'longitud' => -60.5364, 'titulo' => 'Cria. 6°', 'numero' => 6],
            ['latitud' => -31.73711, 'longitud' => -60.45818, 'titulo' => 'Cria. 7°', 'numero' => 7],
            ['latitud' => -31.72208, 'longitud' => -60.51665, 'titulo' => 'Cria. 8°', 'numero' => 8],
            ['latitud' => -31.74051, 'longitud' => -60.55312, 'titulo' => 'Cria. 9°', 'numero' => 9],
            ['latitud' => -31.75655, 'longitud' => -60.51133, 'titulo' => 'Cria. 10°', 'numero' => 10],
            ['latitud' => -31.70670, 'longitud' => -60.56671, 'titulo' => 'Cria. 11°', 'numero' => 11],
            ['latitud' => -31.75109, 'longitud' => -60.48563, 'titulo' => 'Cria. 12°', 'numero' => 12],
            ['latitud' => -31.77106, 'longitud' => -60.52482, 'titulo' => 'Cria. 13°', 'numero' => 13],
            ['latitud' => -31.73017, 'longitud' => -60.49726, 'titulo' => 'Cria. 14°', 'numero' => 14],
            ['latitud' => -31.77032, 'longitud' => -60.48219, 'titulo' => 'Cria. 15°', 'numero' => 15],
            ['latitud' => -31.73434, 'longitud' => -60.55248, 'titulo' => 'Cria. 16°', 'numero' => 16],
            ['latitud' => -31.72189, 'longitud' => -60.54260, 'titulo' => 'Cria. 17°', 'numero' => 17],
        ];
    }
}
