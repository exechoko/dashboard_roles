<?php

namespace App\Observers;

use App\Models\Auditoria;
use App\Models\Destino;
use App\Models\Recurso;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\DB;

class RecursoObserver
{
    /**
     * Handle the Recurso "created" event.
     *
     * @param  \App\Models\Recurso  $recurso
     * @return void
     */
    public function created(Recurso $recurso)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'recurso_modificado_id' => $recurso->id,
                'nombre_tabla' => 'recursos',
                'accion' => 'CREAR RECURSO',
            ]);
            $changes = sprintf(
                'Recurso creado: ID: %d - DEPENDENCIA: %s - VEHICULO: %s - NOMBRE: %s',
                $recurso->id,
                $recurso->destino->nombre,
                (!is_null($recurso->vehiculo) ? $recurso->vehiculo->dominio : '-'),
                $recurso->nombre
            );
            $aud->cambios = $changes;
            $aud->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Handle the Recurso "updated" event.
     *
     * @param  \App\Models\Recurso  $recurso
     * @return void
     */
    public function updated(Recurso $recurso)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'recurso_modificado_id' => $recurso->id,
                'nombre_tabla' => 'recursos',
                'accion' => 'ACTUALIZAR',
            ]);

            $changes = [];
            foreach ($recurso->getChanges() as $key => $value) {
                if ($key != 'updated_at') {
                    $campo = null;
                    $old = null;
                    $new = null;
                    switch ($key) {
                        case 'vehiculo_id':
                            $campo = 'Vehiculo';
                            $old = 'S/D';
                            $vehiculo = Vehiculo::where('id', $recurso->getOriginal($key))->first();
                            if ($vehiculo) {
                                $old = $vehiculo->dominio;
                            }
                            //$old = Vehiculo::where('id', $recurso->getOriginal($key))->first()->dominio;
                            $new = Vehiculo::where('id', $value)->first()->dominio;
                            $changes[] = "$campo: " . $old . ' => ' . $new;
                            break;
                        case 'destino_id':
                            $campo = 'Destino';
                            $old = Destino::where('id', $recurso->getOriginal($key))->first()->nombre;
                            $new = Destino::where('id', $value)->first()->nombre;
                            $changes[] = "$campo: " . $old . ' => ' . $new;
                            break;
                        default:
                            $changes[] = "$key: " . $recurso->getOriginal($key) . ' => ' . $value;
                            break;
                    }
                }
            }
            if (!empty($changes)) {
                $aud->cambios = implode(", ", $changes);
            }
            $aud->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Handle the Recurso "deleted" event.
     *
     * @param  \App\Models\Recurso  $recurso
     * @return void
     */
    public function deleted(Recurso $recurso)
    {
        //
    }

    /**
     * Handle the Recurso "restored" event.
     *
     * @param  \App\Models\Recurso  $recurso
     * @return void
     */
    public function restored(Recurso $recurso)
    {
        //
    }

    /**
     * Handle the Recurso "force deleted" event.
     *
     * @param  \App\Models\Recurso  $recurso
     * @return void
     */
    public function forceDeleted(Recurso $recurso)
    {
        //
    }
}
