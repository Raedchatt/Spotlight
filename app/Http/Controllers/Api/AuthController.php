<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Revendeur;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Cookie;


class AuthController extends Controller
{

    /**
     * Register new user (Initialize verification)
     */
    public function register(Request $request)
    {
        $request->validate([
            'username'  => 'required|string|max:255',
            'role'      => 'required|string|in:participant,organisateur,revendeur',
            'email'     => 'required|email|unique:users',
            'telephone' => 'required|string|max:20',
            'password'  => 'required|confirmed|min:8',
        ]);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store data in session
        $request->session()->put('registration_data', [
            'username'  => $request->username,
            'role'      => $request->role,
            'email'     => $request->email,
            'telephone' => $request->telephone,
            'password'  => Hash::make($request->password),
            'code'      => $code,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Also store code in a cookie as requested (though session is more secure)
        Cookie::queue('verification_code', $code, 15);

        // Send notification
        try {
            Notification::route('mail', $request->email)
                ->notify(new VerifyEmailNotification($code));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Registration mail failed: " . $e->getMessage());
        }

        return redirect()->to('/verify-email');
    }

    /**
     * Verify the email with the code
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $data = $request->session()->get('registration_data');
        $cookieCode = Cookie::get('verification_code');

        if (!$data || now()->gt($data['expires_at'])) {
            return redirect()->route('register')->withErrors(['email' => 'Verification session expired. Please register again.']);
        }

        // Check code from session or cookie
        if ($request->code !== $data['code'] && $request->code !== $cookieCode) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        // Code is correct, create the user
        $user = User::create([
            'username'  => $data['username'],
            'role'      => $data['role'],
            'email'     => $data['email'],
            'telephone' => $data['telephone'],
            'password'  => $data['password'],
            'email_verified_at' => now(),
        ]);

        // Clear session and cookie
        $request->session()->forget('registration_data');
        Cookie::queue(Cookie::forget('verification_code'));

        // Auto login
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