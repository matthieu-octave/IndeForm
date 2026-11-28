<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Mon compte : profil, espace de travail et mot de passe. Export et suppression arrivent au lot E. */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('account.show', [
            'user' => $user,
            'workspace' => $user->currentWorkspace,
            'modules' => config('compte.modules'),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->fill($data);
        $emailChanged = $user->isDirty('email');
        if ($emailChanged) {
            // Nouvelle adresse : elle doit être confirmée à son tour.
            $user->email_verified_at = null;
        }
        $user->save();

        if ($emailChanged) {
            AuditLog::record('email.changed', $user);
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice')->with('status', 'Adresse modifiée : confirmez-la grâce au lien envoyé à '.$user->email.'.');
        }

        return redirect()->route('account.show')->with('status', 'Profil mis à jour.');
    }

    public function updateWorkspace(Request $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspace;
        abort_if($workspace === null, 404);

        $workspace->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'siret' => ['nullable', 'string', 'regex:/^\d{3}\s?\d{3}\s?\d{3}\s?\d{5}$/'],
            'address' => ['nullable', 'string', 'max:1000'],
        ], ['siret.regex' => 'Le SIRET doit comporter 14 chiffres.']));

        return redirect()->route('account.show')->with('status', 'Espace de travail mis à jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], ['current_password.current_password' => 'Le mot de passe actuel est incorrect.']);

        $request->user()->update(['password' => $data['password']]);
        AuditLog::record('password.changed', $request->user());

        return redirect()->route('account.show')->with('status', 'Mot de passe modifié.');
    }
}
