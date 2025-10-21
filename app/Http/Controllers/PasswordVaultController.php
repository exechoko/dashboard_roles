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
        // El index debe mostrar las contraseñas PROPIAS Y las COMPARTIDAS CON el usuario
        $userId = Auth::id();

        // Contraseñas propias
        $queryOwn = PasswordVault::where('user_id', $userId);

        // IDs de las contraseñas compartidas con el usuario
        $sharedIds = PasswordVaultShare::where('shared_with_user_id', $userId)
            ->pluck('password_vault_id');

        // Consulta base combinando propias y compartidas
        $query = PasswordVault::where('user_id', $userId)
            ->orWhereIn('id', $sharedIds)
            ->with(['shares' => function ($q) use ($userId) {
                // Eager loading de los compartidos relevantes, puede usarse en la vista
                $q->where('shared_with_user_id', $userId);
            }]);

        // Aplicar filtros solo a las contraseñas del usuario (o considerar un filtro más complejo)
        // Por simplicidad, aplicaremos los filtros a toda la consulta combinada
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('system_name', 'like', '%' . $request->search . '%')
                  ->orWhere('username', 'like', '%' . $request->search . '%')
                  ->orWhere('url', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('system_type', $request->type);
        }

        if ($request->has('favorites')) {
            $query->where('favorite', true);
        }

        $passwords = $query->orderBy('favorite', 'desc')
            ->orderBy('system_name')
            ->paginate(15);

        $systemTypes = PasswordVault::getSystemTypes();
        $vpnTypes = PasswordVault::getVpnTypes();

        // Necesitas pasar los usuarios para el modal de compartir
        $users = User::where('id', '!=', $userId)->get(['id', 'name', 'email']);


        return view(
            'password_vault.index',
            compact('passwords', 'systemTypes', 'vpnTypes', 'users'));
    }

    public function create()
    {
        $systemTypes = PasswordVault::getSystemTypes();
        $vpnTypes = PasswordVault::getVpnTypes();
        return view('password_vault.create', compact('systemTypes', 'vpnTypes'));
    }

    public function store(Request $request)
    {
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
        // Policy: Verifica si el usuario es dueño O si ha sido compartido con él
        $this->authorize('view', $passwordVault);

        $passwordVault->recordAccess();

        return view('password_vault.show', compact('passwordVault'));
    }

    public function edit(PasswordVault $passwordVault)
    {
        $this->authorize('update', $passwordVault);

        $systemTypes = PasswordVault::getSystemTypes();
        return view('password_vault.create', compact('passwordVault', 'systemTypes'));
    }

    public function update(Request $request, PasswordVault $passwordVault)
    {
        $this->authorize('update', $passwordVault);

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
        $this->authorize('delete', $passwordVault);

        $passwordVault->delete();

        return redirect()->route('password-vault.index')
            ->with('success', 'Contraseña eliminada exitosamente');
    }

    // API endpoint para obtener contraseña (con seguridad adicional)
    public function getPassword(PasswordVault $passwordVault)
    {
        $this->authorize('view', $passwordVault);

        $passwordVault->recordAccess();

        return response()->json([
            'password' => $passwordVault->password
        ]);
    }

    // Generar contraseña segura
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

    // Toggle favorito
    public function toggleFavorite(PasswordVault $passwordVault)
    {
        $this->authorize('update', $passwordVault);

        $passwordVault->update(['favorite' => !$passwordVault->favorite]);

        return response()->json([
            'success' => true,
            'favorite' => $passwordVault->favorite
        ]);
    }

    /**
     * Devuelve los usuarios con los que se ha compartido la contraseña. (AJAX/API)
     */
    public function getShares(PasswordVault $passwordVault)
    {
        // Asegurarse de que SOLO el dueño pueda ver con quién está compartida
        $this->authorize('owner', $passwordVault);

        $shares = $passwordVault->shares()->with('sharedWith')->get();

        // Mapear para una respuesta JSON limpia
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

    /**
     * Comparte una contraseña con otro usuario.
     */
    public function share(Request $request, PasswordVault $passwordVault)
    {
        // Asegurarse de que SOLO el dueño pueda compartir
        $this->authorize('owner', $passwordVault);
        $sharedWithId = $request->input('shared_with_user_id');

        $request->validate([
            'shared_with_user_id' => [
                'required',
                'exists:users,id',
                // Validación para evitar compartir consigo mismo
                Rule::notIn([Auth::id()]),
                // Validación para evitar compartir dos veces la misma contraseña con el mismo usuario
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

        //dd('llegue', $request->all());

        PasswordVaultShare::create([
            'password_vault_id' => $passwordVault->id,
            'shared_with_user_id' => $sharedWithId,
            'shared_by_user_id' => Auth::id(),
            'can_edit' => $request->boolean('can_edit'),
        ]);

        return redirect()->back()->with('success', 'Contraseña compartida exitosamente.');
    }

    /**
     * Revoca el acceso de un usuario a una contraseña compartida.
     */
    public function revokeShare(PasswordVaultShare $share)
    {
        // 1. Verificar que el usuario autenticado sea el dueño de la contraseña compartida
        $passwordVault = $share->vault; // Asumiendo que el modelo share tiene la relación 'vault'
        $this->authorize('owner', $passwordVault);

        $share->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Acceso revocado exitosamente.');
    }
}
