<?php

namespace App\Observers;

use App\Models\Direccion;

class DireccionObserver
{
    /**
     * Handle the Direccion "created" event.
     *
     * @param  \App\Models\Direccion  $direccion
     * @return void
     */
    public function created(Direccion $direccion)
    {
        //
    }

    /**
     * Handle the Direccion "updated" event.
     *
     * @param  \App\Models\Direccion  $direccion
     * @return void
     */
    public function updated(Direccion $direccion)
    {
        //
    }

    /**
     * Handle the Direccion "deleted" event.
     *
     * @param  \App\Models\Direccion  $direccion
     * @return void
     */
    public function deleted(Direccion $direccion)
    {
        //
    }

    /**
     * Handle the Direccion "restored" event.
     *
     * @param  \App\Models\Direccion  $direccion
     * @return void
     */
    public function restored(Direccion $direccion)
    {
        //
    }

    /**
     * Handle the Direccion "force deleted" event.
     *
     * @param  \App\Models\Direccion  $direccion
     * @return void
     */
    public function forceDeleted(Direccion $direccion)
    {
        //
    }
}
