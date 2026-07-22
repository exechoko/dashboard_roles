<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditoriaService
{
    /**
     * Modelos que ya cuentan con su propio Observer dedicado (con mensajes
     * personalizados y las columnas *_modificado_id) por lo que quedan
     * excluidos de la auditoría genérica para no duplicar registros.
     *
     * @var array<int, class-string>
     */
    protected const MODELOS_CON_OBSERVER_PROPIO = [
        \App\Models\User::class,
        \App\Models\FlotaGeneral::class,
        \App\Models\Historico::class,
        \App\Models\Recurso::class,
        \App\Models\Equipo::class,
    ];

    /**
     * Modelos técnicos que no representan información de negocio auditable.
     *
     * @var array<int, class-string>
     */
    protected const MODELOS_EXCLUIDOS = [
        Auditoria::class,
        \Illuminate\Notifications\DatabaseNotification::class,
    ];

    /**
     * Nombres de campo que nunca deben quedar expuestos en el detalle de cambios.
     */
    protected const PATRON_CAMPOS_SENSIBLES = '/password|token|secret|clave|contrasena|contraseña|preshared_key/i';

    /**
     * Registra en la tabla auditoria un evento create/update/delete de un modelo,
     * a partir del listener genérico de eventos Eloquent registrado en
     * EventServiceProvider.
     */
    public static function registrarEventoModelo(string $evento, Model $model): void
    {
        try {
            $clase = get_class($model);

            if (in_array($clase, self::MODELOS_CON_OBSERVER_PROPIO, true)) {
                return;
            }

            if (in_array($clase, self::MODELOS_EXCLUIDOS, true)) {
                return;
            }

            $accion = match ($evento) {
                'created' => 'CREAR',
                'updated' => 'ACTUALIZAR',
                'deleted' => 'ELIMINAR',
                default => strtoupper($evento),
            };

            $cambios = match ($evento) {
                'created', 'deleted' => self::formatearAtributos($model),
                'updated' => self::formatearCambios($model),
                default => null,
            };

            // Si en un update lo único que cambió fue updated_at (touch), no se audita.
            if ($evento === 'updated' && $cambios === null) {
                return;
            }

            self::registrar($accion, $model->getTable(), $cambios);
        } catch (\Exception $e) {
            // La auditoría nunca debe interrumpir la operación de negocio real.
            Log::error('AuditoriaService: error al auditar evento de modelo', [
                'evento' => $evento,
                'modelo' => get_class($model),
                'error'  => $e->getMessage(),
            ]);
        }
    }

    /**
     * Registra un evento arbitrario del sistema (login, logout, envío de mail, etc.)
     * que no proviene de un modelo Eloquent.
     */
    public static function registrar(string $accion, string $nombreTabla, ?string $cambios = null, ?int $userId = null): void
    {
        try {
            Auditoria::create([
                'user_id'      => $userId ?? Auth::id(),
                'nombre_tabla' => $nombreTabla,
                'accion'       => $accion,
                'cambios'      => $cambios,
                'ip_address'   => request()->ip(),
                'user_agent'   => Str::limit((string) request()->userAgent(), 255, ''),
            ]);
        } catch (\Exception $e) {
            Log::error('AuditoriaService: error al registrar auditoría', [
                'accion' => $accion,
                'tabla'  => $nombreTabla,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    protected static function formatearAtributos(Model $model): ?string
    {
        $atributos = collect($model->getAttributes())
            ->except($model->getHidden())
            ->map(fn ($valor, $clave) => self::esSensible($clave)
                ? "$clave: [OCULTO]"
                : "$clave: " . self::valorLegible($valor))
            ->implode(', ');

        return $atributos === '' ? null : $atributos;
    }

    protected static function formatearCambios(Model $model): ?string
    {
        $cambios = [];

        foreach ($model->getChanges() as $clave => $valor) {
            if ($clave === 'updated_at') {
                continue;
            }

            if (self::esSensible($clave)) {
                $cambios[] = "$clave: [OCULTO]";
                continue;
            }

            $cambios[] = "$clave: " . self::valorLegible($model->getOriginal($clave)) . ' => ' . self::valorLegible($valor);
        }

        return empty($cambios) ? null : implode(', ', $cambios);
    }

    protected static function esSensible(string $campo): bool
    {
        return (bool) preg_match(self::PATRON_CAMPOS_SENSIBLES, $campo);
    }

    protected static function valorLegible(mixed $valor): string
    {
        if (is_null($valor)) {
            return 'S/D';
        }

        if (is_bool($valor)) {
            return $valor ? 'true' : 'false';
        }

        return Str::limit((string) $valor, 300);
    }
}
