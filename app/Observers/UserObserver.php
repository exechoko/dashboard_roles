<?php

namespace App\Observers;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'usuario_modificado_id' => $user->id,
                'nombre_tabla' => 'user',
                'accion' => 'CREAR USUARIO',
            ]);
            $changes = sprintf(
                'Usuario creado: ID: %d - NOMBRE: %s %s - LP: %s - EMAIL: %s',
                $user->id,
                $user->name,
                $user->apellido,
                $user->lp,
                $user->email
            );
            $aud->cambios = $changes;
            $aud->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'usuario_modificado_id' => $user->id,
                'nombre_tabla' => 'user',
                'accion' => 'ACTUALIZAR',
            ]);

            $changes = [];
            foreach ($user->getChanges() as $key => $value) {
                $changes[] = "$key: " . $user->getOriginal($key) . ' => ' . $value;
            }
            if (!empty($changes)) {
                $aud->cambios = implode(", ", $changes);
            }
            $aud->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    /**
     * Handle the User "deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'usuario_modificado_id' => $user->id,
                'nombre_tabla' => 'user',
                'accion' => 'ELIMINAR',
            ]);
            $changes = sprintf(
                'Usuario eliminado: ID: %d - NOMBRE: %s %s - LP: %s - EMAIL: %s',
                $user->id,
                $user->name,
                $user->apellido,
                $user->lp,
                $user->email
            );
            $aud->cambios = $changes;
            $aud->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Handle the User "restored" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function restored(User $user)
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        //
    }
}
