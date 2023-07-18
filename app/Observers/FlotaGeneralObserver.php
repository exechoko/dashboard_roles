<?php

namespace App\Observers;

use Illuminate\Support\Facades\DB;
use App\Models\Auditoria;
use App\Models\Equipo;
use App\Models\Recurso;
use App\Models\Destino;
use App\Models\FlotaGeneral;

class FlotaGeneralObserver
{
    /**
     * Handle the FlotaGeneral "created" event.
     *
     * @param  \App\Models\FlotaGeneral  $flotaGeneral
     * @return void
     */
    public function created(FlotaGeneral $flotaGeneral)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'flota_modificado_id' => $flotaGeneral->id,
                'nombre_tabla' => 'flota_general',
                'accion' => 'CREAR FLOTA',
            ]);
            $changes = sprintf(
                'Flota creada: ID: %d - EQUIPO TEI: %s - RECURSO: %s - DESTINO: %s',
                $flotaGeneral->id,
                $flotaGeneral->equipo->tei,
                $flotaGeneral->recurso->nombre,
                $flotaGeneral->destino->nombre
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
     * Handle the FlotaGeneral "updated" event.
     *
     * @param  \App\Models\FlotaGeneral  $flotaGeneral
     * @return void
     */
    public function updated(FlotaGeneral $flotaGeneral)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'flota_modificado_id' => $flotaGeneral->id,
                'nombre_tabla' => 'flota_general',
                'accion' => 'ACTUALIZAR',
            ]);

            $changes = [];
            foreach ($flotaGeneral->getChanges() as $key => $value) {
                if ($key != 'updated_at') {
                    $campo = null;
                    $old = null;
                    $new = null;
                    switch ($key) {
                        case 'equipo_id':
                            $campo = 'Equipo';
                            $old = Equipo::where('id', $flotaGeneral->getOriginal($key))->first()->tei;
                            $new = Equipo::where('id', $value)->first()->tei;
                            $changes[] = "$campo: " . $old . ' => ' . $new;
                            break;
                        case 'recurso_id':
                            $campo = 'Recurso';
                            $old = Recurso::where('id', $flotaGeneral->getOriginal($key))->first()->nombre;
                            $new = Recurso::where('id', $value)->first()->nombre;
                            $changes[] = "$campo: " . $old . ' => ' . $new;
                            break;
                        case 'destino_id':
                            $campo = 'Destino';
                            $old = Destino::where('id', $flotaGeneral->getOriginal($key))->first()->nombre;
                            $new = Destino::where('id', $value)->first()->nombre;
                            $changes[] = "$campo: " . $old . ' => ' . $new;
                            break;
                        default:
                            $changes[] = "$key: " . $flotaGeneral->getOriginal($key) . ' => ' . $value;
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
     * Handle the FlotaGeneral "deleted" event.
     *
     * @param  \App\Models\FlotaGeneral  $flotaGeneral
     * @return void
     */
    public function deleted(FlotaGeneral $flotaGeneral)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'flota_modificado_id' => $flotaGeneral->id,
                'nombre_tabla' => 'flota_general',
                'accion' => 'ELIMINAR',
            ]);
            $changes = sprintf(
                'Flota eliminada: ID: %d - EQUIPO TEI: %s - RECURSO: %s - DESTINO: %s',
                $flotaGeneral->id,
                $flotaGeneral->equipo->tei,
                $flotaGeneral->recurso->nombre,
                $flotaGeneral->destino->nombre
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
     * Handle the FlotaGeneral "restored" event.
     *
     * @param  \App\Models\FlotaGeneral  $flotaGeneral
     * @return void
     */
    public function restored(FlotaGeneral $flotaGeneral)
    {
        //
    }

    /**
     * Handle the FlotaGeneral "force deleted" event.
     *
     * @param  \App\Models\FlotaGeneral  $flotaGeneral
     * @return void
     */
    public function forceDeleted(FlotaGeneral $flotaGeneral)
    {
        //
    }
}
