<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\SessionTemplate;
use App\Support\ModuleExchange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Mon compte : profil, mot de passe, export de mes données et suppression (RGPD). */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.show', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]));

        return redirect()->route('account.show')->with('status', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)],
        ], ['current_password.current_password' => 'Le mot de passe actuel est incorrect.']);

        $request->user()->update(['password' => $data['password']]);

        return redirect()->route('account.show')->with('status', 'Mot de passe modifié.');
    }

    /** Toutes les données de l'espace dans un seul fichier JSON (portabilité). */
    public function export(Request $request): Response
    {
        $user = $request->user();
        $payload = [
            'format' => 'formstudio-account',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'account' => $user->only(['name', 'email', 'created_at']),
            'modules' => CourseModule::orderBy('title')->get()->map(fn ($m) => ModuleExchange::export($m))->all(),
            'session_templates' => SessionTemplate::whereNotNull('workspace_id')->orderBy('name')->get(['name', 'description', 'steps'])->toArray(),
        ];

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="formstudio-mes-donnees-'.now()->format('Y-m-d').'.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(
            ['password' => ['required', 'current_password'], 'confirm' => ['accepted']],
            ['password.current_password' => 'Mot de passe incorrect.', 'confirm.accepted' => 'Cochez la case pour confirmer la suppression.'],
        );

        $user = $request->user();
        // Déconnexion d'abord : la déconnexion réécrit le jeton « rester connecté » de l'utilisateur.
        Auth::logout();
        DB::transaction(function () use ($user) {
            $workspace = $user->workspace;
            $user->delete();
            // Un compte = un espace : l'espace part avec son dernier membre (modules, séances, liens, modèles en cascade).
            if ($workspace && $workspace->users()->doesntExist()) {
                $workspace->delete();
            }
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Votre compte et toutes ses données ont été supprimés.');
    }
}
