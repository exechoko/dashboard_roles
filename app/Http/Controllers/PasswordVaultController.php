<?php

namespace App\Http\Controllers;

use App\Models\PasswordVault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PasswordVaultController extends Controller
{
    public function index(Request $request)
    {
        $query = PasswordVault::where('user_id', Auth::id());

        // Filtros
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->has('favorites')) {
            $query->favorites();
        }

        $passwords = $query->orderBy('favorite', 'desc')
            ->orderBy('system_name')
            ->paginate(15);

        $systemTypes = PasswordVault::getSystemTypes();

        return view('password_vault.index', compact('passwords', 'systemTypes'));
    }

    public function create()
    {
        $systemTypes = PasswordVault::getSystemTypes();
        return view('password_vault.create', compact('systemTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'system_name' => 'required|string|max:255',
            'system_type' => 'required|in:web,vpn,escritorio,base_datos,email,ftp,ssh,otro',
            'url' => 'nullable|url|max:500',
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:1',
            'notes' => 'nullable|string',
            'favorite' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();

        PasswordVault::create($validated);

        return redirect()->route('password-vault.index')
            ->with('success', 'Contraseña guardada exitosamente');
    }

    public function show(PasswordVault $passwordVault)
    {
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
}
