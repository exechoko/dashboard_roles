<?php

namespace App\Observers;

use App\Models\Equipo;

class EquipoObserver
{
    /**
     * Handle the Equipo "created" event.
     *
     * @param  \App\Models\Equipo  $equipo
     * @return void
     */
    public function created(Equipo $equipo)
    {
        //
    }

    /**
     * Handle the Equipo "updated" event.
     *
     * @param  \App\Models\Equipo  $equipo
     * @return void
     */
    public function updated(Equipo $equipo)
    {
        //
    }

    /**
     * Handle the Equipo "deleted" event.
     *
     * @param  \App\Models\Equipo  $equipo
     * @return void
     */
    public function deleted(Equipo $equipo)
    {
        //
    }

    /**
     * Handle the Equipo "restored" event.
     *
     * @param  \App\Models\Equipo  $equipo
     * @return void
     */
    public function restored(Equipo $equipo)
    {
        //
    }

    /**
     * Handle the Equipo "force deleted" event.
     *
     * @param  \App\Models\Equipo  $equipo
     * @return void
     */
    public function forceDeleted(Equipo $equipo)
    {
        //
    }
}
