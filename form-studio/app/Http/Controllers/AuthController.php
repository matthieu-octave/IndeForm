<?php

namespace App\Http\Controllers;

use App\Actions\CreateAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** Inscription ouverte à tous, sur invitation (code), ou fermée. */
    public static function registrationAvailable(): bool
    {
        return config('studio.registration_open') || config('studio.invite_code') !== null;
    }

    public function showRegister(): View
    {
        abort_unless(self::registrationAvailable(), 404);

        return view('auth.register', ['needsCode' => ! config('studio.registration_open')]);
    }

    public function register(Request $request, CreateAccount $createAccount): RedirectResponse
    {
        abort_unless(self::registrationAvailable(), 404);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)],
        ];
        if (! config('studio.registration_open')) {
            $rules['invite_code'] = ['required', 'string', function ($attribute, $value, $fail) {
                if (! hash_equals((string) config('studio.invite_code'), (string) $value)) {
                    $fail('Code d’invitation incorrect.');
                }
            }];
        }
        $data = $request->validate($rules);

        $user = $createAccount($data['name'], $data['email'], $data['password']);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('modules.index');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Identifiants incorrects.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('modules.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
