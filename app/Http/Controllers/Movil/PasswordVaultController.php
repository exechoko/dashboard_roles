<?php

namespace App\Http\Controllers\Movil;

use App\Http\Controllers\Controller;
use App\Http\Middleware\VerifyMasterPassword;
use App\Models\PasswordVault;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordVaultController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ver-clave');
    }

    public function index(Request $request): View
    {
        $texto = trim((string) $request->get('texto'));
        $tipo = (string) $request->get('tipo');
        $soloFavoritos = $request->boolean('favoritos');
        $userId = Auth::id();

        $sharedIds = PasswordVault::query()
            ->whereHas('shares', fn (Builder $query) => $query->where('shared_with_user_id', $userId))
            ->pluck('id');

        $passwords = PasswordVault::query()
            ->where(function (Builder $query) use ($userId, $sharedIds): void {
                $query->where('user_id', $userId)
                    ->orWhereIn('id', $sharedIds);
            })
            ->when($texto !== '', function (Builder $query) use ($texto): void {
                $query->where(function (Builder $q) use ($texto): void {
                    $q->where('system_name', 'like', "%{$texto}%")
                        ->orWhere('username', 'like', "%{$texto}%")
                        ->orWhere('url', 'like', "%{$texto}%");
                });
            })
            ->when($tipo !== '', fn (Builder $query) => $query->where('system_type', $tipo))
            ->when($soloFavoritos, fn (Builder $query) => $query->where('favorite', true))
            ->with('owner')
            ->orderByDesc('favorite')
            ->orderBy('system_name')
            ->paginate(15)
            ->withQueryString();

        $systemTypes = PasswordVault::getSystemTypes();

        return view('movil.passwords.index', compact('passwords', 'systemTypes', 'texto', 'tipo', 'soloFavoritos'));
    }

    public function show(PasswordVault $passwordVault): View
    {
        $userId = Auth::id();
        $isOwner = $passwordVault->user_id === $userId;
        $isShared = $passwordVault->shares()->where('shared_with_user_id', $userId)->exists();

        if (!$isOwner && !$isShared) {
            abort(403, 'No tenés acceso a esta contraseña.');
        }

        $passwordVault->recordAccess();

        $systemTypes = PasswordVault::getSystemTypes();

        return view('movil.passwords.show', compact('passwordVault', 'systemTypes'));
    }

    public function masterPasswordForm(): View|RedirectResponse
    {
        if (empty(Auth::user()->master_password) || VerifyMasterPassword::desbloqueoVigente()) {
            return redirect()->route('movil.password-vault.index');
        }

        return view('movil.passwords.master_password');
    }

    public function verifyMasterPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'master_password' => 'required|string',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->master_password, $user->master_password)) {
            return back()->withErrors(['master_password' => 'Contraseña maestra incorrecta.']);
        }

        VerifyMasterPassword::marcarDesbloqueado();

        $intended = session()->pull('master_password_intended', route('movil.password-vault.index'));

        return redirect($intended)->with('success', 'Acceso al gestor de contraseñas verificado.');
    }
}
