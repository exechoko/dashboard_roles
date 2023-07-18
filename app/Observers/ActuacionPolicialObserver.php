<?php

namespace App\Observers;

use App\Models\ActuacionPolicial;

class ActuacionPolicialObserver
{
    /**
     * Handle the ActuacionPolicial "created" event.
     *
     * @param  \App\Models\ActuacionPolicial  $actuacionPolicial
     * @return void
     */
    public function created(ActuacionPolicial $actuacionPolicial)
    {
        //
    }

    /**
     * Handle the ActuacionPolicial "updated" event.
     *
     * @param  \App\Models\ActuacionPolicial  $actuacionPolicial
     * @return void
     */
    public function updated(ActuacionPolicial $actuacionPolicial)
    {
        //
    }

    /**
     * Handle the ActuacionPolicial "deleted" event.
     *
     * @param  \App\Models\ActuacionPolicial  $actuacionPolicial
     * @return void
     */
    public function deleted(ActuacionPolicial $actuacionPolicial)
    {
        //
    }

    /**
     * Handle the ActuacionPolicial "restored" event.
     *
     * @param  \App\Models\ActuacionPolicial  $actuacionPolicial
     * @return void
     */
    public function restored(ActuacionPolicial $actuacionPolicial)
    {
        //
    }

    /**
     * Handle the ActuacionPolicial "force deleted" event.
     *
     * @param  \App\Models\ActuacionPolicial  $actuacionPolicial
     * @return void
     */
    public function forceDeleted(ActuacionPolicial $actuacionPolicial)
    {
        //
    }
}
