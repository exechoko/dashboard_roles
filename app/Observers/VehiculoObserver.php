<?php

namespace App\Observers;

use App\Models\Vehiculo;

class VehiculoObserver
{
    /**
     * Handle the Vehiculo "created" event.
     *
     * @param  \App\Models\Vehiculo  $vehiculo
     * @return void
     */
    public function created(Vehiculo $vehiculo)
    {
        //
    }

    /**
     * Handle the Vehiculo "updated" event.
     *
     * @param  \App\Models\Vehiculo  $vehiculo
     * @return void
     */
    public function updated(Vehiculo $vehiculo)
    {
        //
    }

    /**
     * Handle the Vehiculo "deleted" event.
     *
     * @param  \App\Models\Vehiculo  $vehiculo
     * @return void
     */
    public function deleted(Vehiculo $vehiculo)
    {
        //
    }

    /**
     * Handle the Vehiculo "restored" event.
     *
     * @param  \App\Models\Vehiculo  $vehiculo
     * @return void
     */
    public function restored(Vehiculo $vehiculo)
    {
        //
    }

    /**
     * Handle the Vehiculo "force deleted" event.
     *
     * @param  \App\Models\Vehiculo  $vehiculo
     * @return void
     */
    public function forceDeleted(Vehiculo $vehiculo)
    {
        //
    }
}
