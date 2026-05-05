<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //Sin paginación
        /* $usuarios = User::all();
        return view('usuarios.index',compact('usuarios')); */

        //Con paginación
        $usuarios = User::paginate(100);
        return view('usuarios.index', compact('usuarios'));

        //al usar esta paginacion, recordar poner en el el index.blade.php este codigo  {!! $usuarios->links() !!}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //aqui trabajamos con name de las tablas de users
        $roles = Role::pluck('name', 'name')->all();
        return view('usuarios.crear', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'apellido' => 'required',
            'lp' => 'required',
            'dni' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:confirm-password',
            'roles' => 'required'
        ]);

        //Para no guardar el mismo usuario 2 veces
        $u = User::where('lp', $request->lp)->first();
        if (!is_null($u)) {
            return back()->with('error', 'Ya se encuentra un usuario con el mismo LP');//->withInput();
        }

        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
        $input['acceso_externo'] = $request->boolean('acceso_externo');

        $user = User::create($input);
        $user->assignRole($request->input('roles'));

        return redirect()->route('usuarios.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::find($id);
        $roles = Role::pluck('name', 'name')->all();
        $userRole = $user->roles->pluck('name', 'name')->all();

        return view('usuarios.editar', compact('user', 'roles', 'userRole'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'apellido' => 'required',
            'lp' => 'required',
            'dni' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'same:confirm-password',
            'master_password' => 'nullable|string|min:4|same:confirm_master_password',
            'roles' => 'required'
        ], [
            'master_password.same' => 'Las contraseñas maestras no coinciden.',
            'master_password.min' => 'La contraseña maestra debe tener al menos 4 caracteres.',
        ]);

        $input = $request->all();

        // Contraseña de login
        if (!empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
        } else {
            $input = Arr::except($input, ['password']);
        }

        // Contraseña maestra del gestor
        if ($request->boolean('clear_master_password')) {
            $input['master_password'] = null;
        } elseif (!empty($input['master_password'])) {
            $input['master_password'] = Hash::make($input['master_password']);
        } else {
            $input = Arr::except($input, ['master_password']);
        }

        $input['acceso_externo'] = $request->boolean('acceso_externo');
        $input = Arr::except($input, ['confirm_master_password', 'clear_master_password']);

        $user = User::find($id);
        $user->update($input);
        DB::table('model_has_roles')->where('model_id', $id)->delete();

        $user->assignRole($request->input('roles'));

        return redirect()->route('usuarios.index');
    }

    /**
     * Update user profile with photo
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $request->user_id,
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // máximo 2MB
        ]);

        $user = User::findOrFail($request->user_id);

        $input = $request->only(['name', 'email']);

        // Manejo de la foto
        if ($request->hasFile('photo')) {
            // Eliminar foto anterior si existe
            if ($user->photo && file_exists(public_path($user->photo))) {
                unlink(public_path($user->photo));
            }

            // Guardar nueva foto
            $image = $request->file('photo');
            $imageName = 'profile_' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/profiles'), $imageName);
            $input['photo'] = 'uploads/profiles/' . $imageName;
        }

        $user->update($input);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'photo_url' => $user->photo ? asset($user->photo) : asset('img/logo.png')
        ]);
    }

    /**
     * Update own login password (requires current password verification)
     */
    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            'current_password'    => 'required|string',
            'new_password'        => 'required|string|min:6|same:confirm_new_password',
            'confirm_new_password'=> 'required|string',
        ], [
            'new_password.min'  => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'new_password.same' => 'Las contraseñas nuevas no coinciden.',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual es incorrecta.',
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña de acceso actualizada correctamente.',
        ]);
    }

    /**
     * Update own master password for the password vault
     */
    public function updateMasterPassword(Request $request)
    {
        $this->validate($request, [
            'master_password'         => 'nullable|string|min:4|same:confirm_master_password',
            'confirm_master_password' => 'nullable|string',
        ], [
            'master_password.min'  => 'La contraseña maestra debe tener al menos 4 caracteres.',
            'master_password.same' => 'Las contraseñas maestras no coinciden.',
        ]);

        $user = auth()->user();

        if ($request->boolean('clear_master_password')) {
            $user->master_password = null;
            $user->save();
            session()->forget('master_password_verified');
            return response()->json([
                'success' => true,
                'message' => 'Contraseña maestra eliminada. El gestor de contraseñas ya no tiene protección adicional.',
            ]);
        }

        if (empty($request->master_password)) {
            return response()->json([
                'success' => false,
                'message' => 'Ingrese una contraseña maestra o marque la opción de eliminar.',
            ], 422);
        }

        $user->master_password = Hash::make($request->master_password);
        $user->save();
        session()->forget('master_password_verified');

        return response()->json([
            'success' => true,
            'message' => 'Contraseña maestra configurada correctamente.',
        ]);
    }

    /**
     * Update user theme preference
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateTheme(Request $request)
    {
        $this->validate($request, [
            'theme' => 'required|in:light,dark'
        ]);

        $user = auth()->user();
        $user->theme = $request->theme;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Tema actualizado correctamente',
            'theme' => $user->theme
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        User::find($id)->delete();
        return redirect()->route('usuarios.index');
    }
}
