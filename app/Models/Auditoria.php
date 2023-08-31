<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $table = 'auditoria';
    protected $fillable = ['user_id',
        'usuario_modificado_id',
        'flota_modificado_id',
        'act_pol_modificado_id',
        'camara_modificado_id',
        'comisaria_modificado_id',
        'departamental_modificado_id',
        'destino_modificado_id',
        'division_modificado_id',
        'direccion_modificado_id',
        'empresa_sop_modificado_id',
        'equipo_modificado_id',
        'historico_modificado_id',
        'recurso_modificado_id',
        'seccion_modificado_id',
        'vehiculo_modificado_id',
        'nombre_tabla',
        'cambios',
        'accion'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function usuarioModificado()
    {
        return $this->belongsTo(User::class, 'usuario_modificado_id');
    }
    public function flotaModificada()
    {
        return $this->belongsTo(FlotaGeneral::class, 'flota_modificado_id');
    }
    public function historicoModificado()
    {
        return $this->belongsTo(Historico::class, 'historico_modificado_id');
    }
    public function recursoModificado()
    {
        return $this->belongsTo(Recurso::class, 'recurso_modificado_id');
    }

    //Agregar para todos las demás clases
}
