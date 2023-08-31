<?php

namespace App\Observers;

use Illuminate\Support\Facades\DB;
use App\Models\Historico;
use App\Models\Auditoria;
use App\Models\Equipo;
use App\Models\Recurso;
use App\Models\Destino;
use App\Models\FlotaGeneral;

class HistoricoObserver
{
    /**
     * Handle the Historico "created" event.
     *
     * @param  \App\Models\Historico  $historico
     * @return void
     */
    public function created(Historico $historico)
    {
        //
    }

    /**
     * Handle the Historico "updated" event.
     *
     * @param  \App\Models\Historico  $historico
     * @return void
     */
    public function updated(Historico $historico)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'historico_modificado_id' => $historico->id,
                'nombre_tabla' => 'historico',
                'accion' => 'ACTUALIZAR',
            ]);
            //dd($aud);

            $changes = [];
            foreach ($historico->getChanges() as $key => $value) {
                if ($key != 'updated_at') {
                    $campo = null;
                    $old = null;
                    $new = null;
                    switch ($key) {
                        case 'equipo_id':
                            $campo = 'Equipo';
                            $old = Equipo::where('id', $historico->getOriginal($key))->first()->tei;
                            $new = Equipo::where('id', $value)->first()->tei;
                            $changes[] = "$campo: " . $old . ' => ' . $new;
                            break;
                        case 'recurso_id':
                            $campo = 'Recurso';
                            $old = Recurso::where('id', $historico->getOriginal($key))->first()->nombre;
                            $new = Recurso::where('id', $value)->first()->nombre;
                            $changes[] = "$campo: " . $old . ' => ' . $new;
                            break;
                        case 'destino_id':
                            $campo = 'Destino';
                            $old = Destino::where('id', $historico->getOriginal($key))->first()->nombre;
                            $new = Destino::where('id', $value)->first()->nombre;
                            $changes[] = "$campo: " . $old . ' => ' . $new;
                            break;
                        default:
                            $changes[] = "$key: " . $historico->getOriginal($key) . ' => ' . $value;
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
     * Handle the Historico "deleted" event.
     *
     * @param  \App\Models\Historico  $historico
     * @return void
     */
    public function deleted(Historico $historico)
    {
        //
    }

    /**
     * Handle the Historico "restored" event.
     *
     * @param  \App\Models\Historico  $historico
     * @return void
     */
    public function restored(Historico $historico)
    {
        //
    }

    /**
     * Handle the Historico "force deleted" event.
     *
     * @param  \App\Models\Historico  $historico
     * @return void
     */
    public function forceDeleted(Historico $historico)
    {
        //
    }
}
