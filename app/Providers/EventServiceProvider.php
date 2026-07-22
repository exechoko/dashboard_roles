<?php

namespace App\Providers;

use App\Models\Equipo;
use App\Models\FlotaGeneral;
use App\Models\Historico;
use App\Models\Recurso;
use App\Models\User;
use App\Observers\EquipoObserver;
use App\Observers\FlotaGeneralObserver;
use App\Observers\HistoricoObserver;
use App\Observers\RecursoObserver;
use App\Observers\UserObserver;
use App\Listeners\AuditFailedLoginListener;
use App\Listeners\AuditLoginListener;
use App\Listeners\AuditLogoutListener;
use App\Listeners\AuditMailSentListener;
use App\Listeners\TelegramJobCompletadoListener;
use App\Listeners\TelegramJobFallidoListener;
use App\Services\AuditoriaService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
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
        JobProcessed::class => [
            TelegramJobCompletadoListener::class,
        ],
        JobFailed::class => [
            TelegramJobFallidoListener::class,
        ],
        Login::class => [
            AuditLoginListener::class,
        ],
        Logout::class => [
            AuditLogoutListener::class,
        ],
        Failed::class => [
            AuditFailedLoginListener::class,
        ],
        MessageSent::class => [
            AuditMailSentListener::class,
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
        Equipo::observe(EquipoObserver::class);

        // Auditoría genérica: captura automáticamente creaciones, modificaciones
        // y eliminaciones de cualquier modelo Eloquent del sistema (salvo los
        // excluidos en AuditoriaService), sin necesidad de un Observer por modelo.
        Event::listen('eloquent.created: *', function (string $eventName, array $data): void {
            AuditoriaService::registrarEventoModelo('created', $data[0]);
        });
        Event::listen('eloquent.updated: *', function (string $eventName, array $data): void {
            AuditoriaService::registrarEventoModelo('updated', $data[0]);
        });
        Event::listen('eloquent.deleted: *', function (string $eventName, array $data): void {
            AuditoriaService::registrarEventoModelo('deleted', $data[0]);
        });
    }
}
