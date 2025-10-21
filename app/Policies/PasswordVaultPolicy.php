<?php

namespace App\Policies;

use App\Models\PasswordVault;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PasswordVaultPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     *  Determine whether the user can view the model.
     * @param \App\Models\User $user
     * @param \App\Models\PasswordVault $passwordVault
     * @return bool
     */
    public function view(User $user, PasswordVault $passwordVault)
    {
        return $user->id === $passwordVault->user_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PasswordVault  $passwordVault
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, PasswordVault $passwordVault)
    {
        return $user->id === $passwordVault->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PasswordVault  $passwordVault
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, PasswordVault $passwordVault)
    {
        return $user->id === $passwordVault->user_id;
    }
}
