<?php

namespace App\Observers;

use App\Models\Camara;

class CamaraObserver
{
    /**
     * Handle the Camara "created" event.
     *
     * @param  \App\Models\Camara  $camara
     * @return void
     */
    public function created(Camara $camara)
    {
        //
    }

    /**
     * Handle the Camara "updated" event.
     *
     * @param  \App\Models\Camara  $camara
     * @return void
     */
    public function updated(Camara $camara)
    {
        //
    }

    /**
     * Handle the Camara "deleted" event.
     *
     * @param  \App\Models\Camara  $camara
     * @return void
     */
    public function deleted(Camara $camara)
    {
        //
    }

    /**
     * Handle the Camara "restored" event.
     *
     * @param  \App\Models\Camara  $camara
     * @return void
     */
    public function restored(Camara $camara)
    {
        //
    }

    /**
     * Handle the Camara "force deleted" event.
     *
     * @param  \App\Models\Camara  $camara
     * @return void
     */
    public function forceDeleted(Camara $camara)
    {
        //
    }
}
