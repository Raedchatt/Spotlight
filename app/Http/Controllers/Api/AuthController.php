<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Revendeur;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    /**
     * Register new user
     */
    public function register(Request $request)
    {
        // Using $request->validate() instead of Validator::make() so that
        // on failure Laravel automatically redirects back with session errors —
        // which is what Inertia's router.post() expects.
        $request->validate([
            'username'  => 'required|string|max:255',
            'role'      => 'required|string|in:participant,organisateur,revendeur',
            'email'     => 'required|email|unique:users',
            'telephone' => 'required|string|max:20',
            'password'  => 'required|confirmed|min:8',
        ]);

        $user = User::create([
            'username'  => $request->username,
            'role'      => $request->role,
            'email'     => $request->email,
            'telephone' => $request->telephone,
            'password'  => Hash::make($request->password),
        ]);

        // Auto login after registration
        Auth::login($user);
        $request->session()->regenerate();

        if ($user->role === \App\Enums\Role::Participant) {
            return redirect()->to('/discovery');
        }

        if ($user->role === \App\Enums\Role::Revendeur) {
            return redirect()->to('/affiliate/dashboard');
        }

        return redirect()->to('/dashboard');
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->role === \App\Enums\Role::Participant) {
                return redirect()->to('/discovery');
            }

            if ($user->role === \App\Enums\Role::Revendeur) {
                return redirect()->intended('/affiliate/dashboard');
            }

            return redirect()->intended('/dashboard');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        return back()->withErrors(['email' => 'Invalid credentials']);
    }
    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}