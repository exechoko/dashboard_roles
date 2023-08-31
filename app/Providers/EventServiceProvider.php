<?php

namespace App\Providers;

use App\Models\FlotaGeneral;
use App\Models\Historico;
use App\Models\Recurso;
use App\Models\User;
use App\Observers\FlotaGeneralObserver;
use App\Observers\HistoricoObserver;
use App\Observers\RecursoObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Events\PermissionAssigned;
use Spatie\Permission\Events\PermissionRevoked;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        User::observe(UserObserver::class);
        FlotaGeneral::observe(FlotaGeneralObserver::class);
        Historico::observe(HistoricoObserver::class);
        Recurso::observe(RecursoObserver::class);
    }
}
