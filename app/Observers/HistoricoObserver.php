<?php

namespace App\Observers;

use App\Models\Historico;

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
        //
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
