<?php

namespace App\Observers;

use App\Models\Destacamento;

class DestacamentoObserver
{
    /**
     * Handle the Destacamento "created" event.
     *
     * @param  \App\Models\Destacamento  $destacamento
     * @return void
     */
    public function created(Destacamento $destacamento)
    {
        //
    }

    /**
     * Handle the Destacamento "updated" event.
     *
     * @param  \App\Models\Destacamento  $destacamento
     * @return void
     */
    public function updated(Destacamento $destacamento)
    {
        //
    }

    /**
     * Handle the Destacamento "deleted" event.
     *
     * @param  \App\Models\Destacamento  $destacamento
     * @return void
     */
    public function deleted(Destacamento $destacamento)
    {
        //
    }

    /**
     * Handle the Destacamento "restored" event.
     *
     * @param  \App\Models\Destacamento  $destacamento
     * @return void
     */
    public function restored(Destacamento $destacamento)
    {
        //
    }

    /**
     * Handle the Destacamento "force deleted" event.
     *
     * @param  \App\Models\Destacamento  $destacamento
     * @return void
     */
    public function forceDeleted(Destacamento $destacamento)
    {
        //
    }
}
