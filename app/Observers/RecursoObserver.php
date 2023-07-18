<?php

namespace App\Observers;

use App\Models\Recurso;

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
        //
    }

    /**
     * Handle the Recurso "updated" event.
     *
     * @param  \App\Models\Recurso  $recurso
     * @return void
     */
    public function updated(Recurso $recurso)
    {
        //
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
