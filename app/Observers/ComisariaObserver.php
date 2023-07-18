<?php

namespace App\Observers;

use App\Models\Comisaria;

class ComisariaObserver
{
    /**
     * Handle the Comisaria "created" event.
     *
     * @param  \App\Models\Comisaria  $comisaria
     * @return void
     */
    public function created(Comisaria $comisaria)
    {
        //
    }

    /**
     * Handle the Comisaria "updated" event.
     *
     * @param  \App\Models\Comisaria  $comisaria
     * @return void
     */
    public function updated(Comisaria $comisaria)
    {
        //
    }

    /**
     * Handle the Comisaria "deleted" event.
     *
     * @param  \App\Models\Comisaria  $comisaria
     * @return void
     */
    public function deleted(Comisaria $comisaria)
    {
        //
    }

    /**
     * Handle the Comisaria "restored" event.
     *
     * @param  \App\Models\Comisaria  $comisaria
     * @return void
     */
    public function restored(Comisaria $comisaria)
    {
        //
    }

    /**
     * Handle the Comisaria "force deleted" event.
     *
     * @param  \App\Models\Comisaria  $comisaria
     * @return void
     */
    public function forceDeleted(Comisaria $comisaria)
    {
        //
    }
}
