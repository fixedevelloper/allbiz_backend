<?php


namespace App\Http\Controllers\web;


use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Formulaire de connexion
     */
    public function loginForm()
    {
        return view('auth.login');
    }

    /**
     * Traitement connexion
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {

        $credentials = $request->validate([
            'phone'    => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        if (!Auth::attempt(
            ['phone' => $credentials['phone'], 'password' => $credentials['password']],
            $request->boolean('remember')
        )) {
            return back()
                ->withErrors(['phone' => 'Numéro ou mot de passe incorrect'])
                ->withInput();
        }

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }


    /**
     * Formulaire inscription
     */
    public function registerForm()
    {
        return view('auth.register');
    }

    /**
     * Traitement inscription
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);

        return redirect('/dashboard');
    }

    /**
     * Déconnexion
     * @param Request $request
     * @return \Illuminate\Container\Container|\Illuminate\Container\TClass|object
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

