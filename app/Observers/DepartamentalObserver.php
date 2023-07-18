<?php

namespace App\Observers;

use App\Models\Departamental;

class DepartamentalObserver
{
    /**
     * Handle the Departamental "created" event.
     *
     * @param  \App\Models\Departamental  $departamental
     * @return void
     */
    public function created(Departamental $departamental)
    {
        //
    }

    /**
     * Handle the Departamental "updated" event.
     *
     * @param  \App\Models\Departamental  $departamental
     * @return void
     */
    public function updated(Departamental $departamental)
    {
        //
    }

    /**
     * Handle the Departamental "deleted" event.
     *
     * @param  \App\Models\Departamental  $departamental
     * @return void
     */
    public function deleted(Departamental $departamental)
    {
        //
    }

    /**
     * Handle the Departamental "restored" event.
     *
     * @param  \App\Models\Departamental  $departamental
     * @return void
     */
    public function restored(Departamental $departamental)
    {
        //
    }

    /**
     * Handle the Departamental "force deleted" event.
     *
     * @param  \App\Models\Departamental  $departamental
     * @return void
     */
    public function forceDeleted(Departamental $departamental)
    {
        //
    }
}
