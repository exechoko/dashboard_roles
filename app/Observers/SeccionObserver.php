<?php

namespace App\Observers;

use App\Models\Seccion;

class SeccionObserver
{
    /**
     * Handle the Seccion "created" event.
     *
     * @param  \App\Models\Seccion  $seccion
     * @return void
     */
    public function created(Seccion $seccion)
    {
        //
    }

    /**
     * Handle the Seccion "updated" event.
     *
     * @param  \App\Models\Seccion  $seccion
     * @return void
     */
    public function updated(Seccion $seccion)
    {
        //
    }

    /**
     * Handle the Seccion "deleted" event.
     *
     * @param  \App\Models\Seccion  $seccion
     * @return void
     */
    public function deleted(Seccion $seccion)
    {
        //
    }

    /**
     * Handle the Seccion "restored" event.
     *
     * @param  \App\Models\Seccion  $seccion
     * @return void
     */
    public function restored(Seccion $seccion)
    {
        //
    }

    /**
     * Handle the Seccion "force deleted" event.
     *
     * @param  \App\Models\Seccion  $seccion
     * @return void
     */
    public function forceDeleted(Seccion $seccion)
    {
        //
    }
}
