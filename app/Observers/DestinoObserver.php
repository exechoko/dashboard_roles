<?php

namespace App\Observers;

use App\Models\Destino;

class DestinoObserver
{
    /**
     * Handle the Destino "created" event.
     *
     * @param  \App\Models\Destino  $destino
     * @return void
     */
    public function created(Destino $destino)
    {
        //
    }

    /**
     * Handle the Destino "updated" event.
     *
     * @param  \App\Models\Destino  $destino
     * @return void
     */
    public function updated(Destino $destino)
    {
        //
    }

    /**
     * Handle the Destino "deleted" event.
     *
     * @param  \App\Models\Destino  $destino
     * @return void
     */
    public function deleted(Destino $destino)
    {
        //
    }

    /**
     * Handle the Destino "restored" event.
     *
     * @param  \App\Models\Destino  $destino
     * @return void
     */
    public function restored(Destino $destino)
    {
        //
    }

    /**
     * Handle the Destino "force deleted" event.
     *
     * @param  \App\Models\Destino  $destino
     * @return void
     */
    public function forceDeleted(Destino $destino)
    {
        //
    }
}
