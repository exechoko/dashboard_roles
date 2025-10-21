<?php

namespace App\Http\Controllers;

use App\Models\PasswordVault;
use App\Models\PasswordVaultShare;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PasswordVaultController extends Controller
{
    public function index(Request $request)
    {
        // Verificar permiso general
        if (!Auth::user()->can('ver-clave')) {
            abort(403, 'No tienes permiso para ver las contraseñas.');
        }

        $userId = Auth::id();

        // IDs de las contraseñas compartidas con el usuario
        $sharedIds = PasswordVaultShare::where('shared_with_user_id', $userId)
            ->pluck('password_vault_id')
            ->toArray();

        // Consulta base: contraseñas propias O compartidas
        $query = PasswordVault::where(function ($q) use ($userId, $sharedIds) {
            $q->where('user_id', $userId)
              ->orWhereIn('id', $sharedIds);
        });

        // Aplicar filtros de búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('system_name', 'like', '%' . $search . '%')
                  ->orWhere('username', 'like', '%' . $search . '%')
                  ->orWhere('url', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('system_type', $request->type);
        }

        if ($request->filled('favorites') && $request->favorites == '1') {
            $query->where('favorite', true);
        }

        // Eager loading de las comparticiones
        $query->with(['shares' => function ($q) use ($userId) {
            $q->where('shared_with_user_id', $userId);
        }]);

        // Ordenar y paginar
        $passwords = $query->orderBy('favorite', 'desc')
            ->orderBy('system_name')
            ->paginate(15)
            ->appends($request->all());

        $systemTypes = PasswordVault::getSystemTypes();
        $vpnTypes = PasswordVault::getVpnTypes();

        // Usuarios para el modal de compartir
        $users = User::where('id', '!=', $userId)->get(['id', 'name', 'email']);

        return view(
            'password_vault.index',
            compact('passwords', 'systemTypes', 'vpnTypes', 'users')
        );
    }

    public function create()
    {
        if (!Auth::user()->can('crear-clave')) {
            abort(403, 'No tienes permiso para crear contraseñas.');
        }

        $systemTypes = PasswordVault::getSystemTypes();
        $vpnTypes = PasswordVault::getVpnTypes();
        return view('password_vault.create', compact('systemTypes', 'vpnTypes'));
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('crear-clave')) {
            abort(403, 'No tienes permiso para crear contraseñas.');
        }

        $validated = $request->validate([
            'system_name' => 'required|string|max:255',
            'system_type' => 'required|in:web,vpn,escritorio,base_datos,email,ftp,ssh,otro',
            'url' => 'nullable|url|max:500',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|min:1',
            'notes' => 'nullable|string',
            'favorite' => 'boolean',
            'vpn_host' => 'nullable|string|max:255',
            'vpn_type' => 'nullable|string|max:255',
            'vpn_preshared_key' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = Auth::id();

        PasswordVault::create($validated);

        return redirect()->route('password-vault.index')
            ->with('success', 'Contraseña guardada exitosamente');
    }

    public function show(PasswordVault $passwordVault)
    {
        // Verificar permiso general
        if (!Auth::user()->can('ver-clave')) {
            abort(403, 'No tienes permiso para ver contraseñas.');
        }

        // Verificar que sea dueño O que esté compartida con él
        $userId = Auth::id();
        $isOwner = $passwordVault->user_id === $userId;
        $isShared = $passwordVault->shares()->where('shared_with_user_id', $userId)->exists();

        if (!$isOwner && !$isShared) {
            abort(403, 'No tienes acceso a esta contraseña.');
        }

        $passwordVault->recordAccess();

        return view('password_vault.show', compact('passwordVault'));
    }

    public function edit(PasswordVault $passwordVault)
    {
        // Verificar permiso general
        if (!Auth::user()->can('editar-clave')) {
            abort(403, 'No tienes permiso para editar contraseñas.');
        }

        // Verificar que sea dueño O que tenga permiso de edición compartida
        $userId = Auth::id();
        $isOwner = $passwordVault->user_id === $userId;

        $share = $passwordVault->shares()->where('shared_with_user_id', $userId)->first();
        $canEdit = $share && $share->can_edit;

        if (!$isOwner && !$canEdit) {
            abort(403, 'No tienes permiso para editar esta contraseña.');
        }

        $systemTypes = PasswordVault::getSystemTypes();
        $vpnTypes = PasswordVault::getVpnTypes();
        return view('password_vault.create', compact('passwordVault', 'systemTypes', 'vpnTypes'));
    }

    public function update(Request $request, PasswordVault $passwordVault)
    {
        // Verificar permiso general
        if (!Auth::user()->can('editar-clave')) {
            abort(403, 'No tienes permiso para editar contraseñas.');
        }

        // Verificar que sea dueño O que tenga permiso de edición compartida
        $userId = Auth::id();
        $isOwner = $passwordVault->user_id === $userId;

        $share = $passwordVault->shares()->where('shared_with_user_id', $userId)->first();
        $canEdit = $share && $share->can_edit;

        if (!$isOwner && !$canEdit) {
            abort(403, 'No tienes permiso para editar esta contraseña.');
        }

        $validated = $request->validate([
            'system_name' => 'required|string|max:255',
            'system_type' => 'required|in:web,vpn,escritorio,base_datos,email,ftp,ssh,otro',
            'url' => 'nullable|url|max:500',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|min:1',
            'notes' => 'nullable|string',
            'favorite' => 'boolean',
            'vpn_host' => 'nullable|string|max:255',
            'vpn_type' => 'nullable|string|max:255',
            'vpn_preshared_key' => 'nullable|string|max:255',
        ]);

        // Solo actualizar password si se proporciona uno nuevo
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $passwordVault->update($validated);

        return redirect()->route('password-vault.index')
            ->with('success', 'Contraseña actualizada exitosamente');
    }

    public function destroy(PasswordVault $passwordVault)
    {
        // Verificar permiso general
        if (!Auth::user()->can('borrar-clave')) {
            abort(403, 'No tienes permiso para eliminar contraseñas.');
        }

        // Solo el dueño puede eliminar
        if ($passwordVault->user_id !== Auth::id()) {
            abort(403, 'Solo el dueño puede eliminar esta contraseña.');
        }

        $passwordVault->delete();

        return redirect()->route('password-vault.index')
            ->with('success', 'Contraseña eliminada exitosamente');
    }

    public function getPassword(PasswordVault $passwordVault)
    {
        // Verificar permiso general
        if (!Auth::user()->can('ver-clave')) {
            abort(403, 'No tienes permiso para ver contraseñas.');
        }

        // Verificar que sea dueño O que esté compartida con él
        $userId = Auth::id();
        $isOwner = $passwordVault->user_id === $userId;
        $isShared = $passwordVault->shares()->where('shared_with_user_id', $userId)->exists();

        if (!$isOwner && !$isShared) {
            abort(403, 'No tienes acceso a esta contraseña.');
        }

        $passwordVault->recordAccess();

        return response()->json([
            'password' => $passwordVault->password
        ]);
    }

    public function generatePassword()
    {
        $length = 16;
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return response()->json(['password' => $password]);
    }

    public function toggleFavorite(PasswordVault $passwordVault)
    {
        // Verificar permiso general
        if (!Auth::user()->can('editar-clave')) {
            abort(403, 'No tienes permiso para editar contraseñas.');
        }

        // Verificar que sea dueño O que tenga permiso de edición compartida
        $userId = Auth::id();
        $isOwner = $passwordVault->user_id === $userId;

        $share = $passwordVault->shares()->where('shared_with_user_id', $userId)->first();
        $canEdit = $share && $share->can_edit;

        if (!$isOwner && !$canEdit) {
            return response()->json(['error' => 'No tienes permiso para editar esta contraseña.'], 403);
        }

        $passwordVault->update(['favorite' => !$passwordVault->favorite]);

        return response()->json([
            'success' => true,
            'favorite' => $passwordVault->favorite
        ]);
    }

    public function getShares(PasswordVault $passwordVault)
    {
        // Verificar permiso general
        if (!Auth::user()->can('compartir-clave')) {
            abort(403, 'No tienes permiso para gestionar compartidos.');
        }

        // Solo el dueño puede ver con quién está compartida
        if ($passwordVault->user_id !== Auth::id()) {
            abort(403, 'Solo el dueño puede ver los compartidos.');
        }

        $shares = $passwordVault->shares()->with('sharedWith')->get();

        $sharesData = $shares->map(function ($share) {
            return [
                'id' => $share->id,
                'shared_with_id' => $share->shared_with_user_id,
                'shared_with_name' => $share->sharedWith->name ?? 'Usuario Eliminado',
                'shared_with_email' => $share->sharedWith->email ?? 'N/A',
                'can_edit' => (bool) $share->can_edit,
            ];
        });

        return response()->json(['shares' => $sharesData]);
    }

    public function share(Request $request, PasswordVault $passwordVault)
    {
        // Verificar permiso general
        if (!Auth::user()->can('compartir-clave')) {
            abort(403, 'No tienes permiso para compartir contraseñas.');
        }

        // Solo el dueño puede compartir
        if ($passwordVault->user_id !== Auth::id()) {
            abort(403, 'Solo el dueño puede compartir esta contraseña.');
        }

        $sharedWithId = $request->input('shared_with_user_id');

        $request->validate([
            'shared_with_user_id' => [
                'required',
                'exists:users,id',
                Rule::notIn([Auth::id()]),
                Rule::unique('password_vault_shares')->where(function ($query) use ($passwordVault, $sharedWithId) {
                    return $query->where('password_vault_id', $passwordVault->id)
                                 ->where('shared_with_user_id', $sharedWithId);
                })
            ],
            'can_edit' => 'boolean',
        ], [
            'shared_with_user_id.unique' => 'Esta contraseña ya está compartida con este usuario.',
            'shared_with_user_id.not_in' => 'No puedes compartir una contraseña contigo mismo.',
        ]);

        PasswordVaultShare::create([
            'password_vault_id' => $passwordVault->id,
            'shared_with_user_id' => $sharedWithId,
            'shared_by_user_id' => Auth::id(),
            'can_edit' => $request->boolean('can_edit'),
        ]);

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Contraseña compartida exitosamente.']);
        }

        return redirect()->back()->with('success', 'Contraseña compartida exitosamente.');
    }

    public function revokeShare(PasswordVaultShare $share)
    {
        // Verificar permiso general
        if (!Auth::user()->can('compartir-clave')) {
            abort(403, 'No tienes permiso para gestionar compartidos.');
        }

        $passwordVault = $share->vault;

        // Solo el dueño puede revocar
        if ($passwordVault->user_id !== Auth::id()) {
            abort(403, 'Solo el dueño puede revocar accesos.');
        }

        $share->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Acceso revocado exitosamente.');
    }
}
