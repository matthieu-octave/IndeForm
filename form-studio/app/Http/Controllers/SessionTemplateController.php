<?php

namespace App\Http\Controllers;

use App\Models\SessionTemplate;
use App\Models\TeachingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionTemplateController extends Controller
{
    public function index(): View
    {
        $templates = SessionTemplate::orderByRaw('workspace_id is not null')->orderBy('name')->get();

        return view('templates.index', [
            'provided' => $templates->filter->isProvided(),
            'mine' => $templates->reject->isProvided(),
        ]);
    }

    /** Enregistre le déroulé d'une séance comme modèle personnel. */
    public function storeFromSession(Request $request, TeachingSession $session): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $session->load('steps');
        SessionTemplate::fromSession($session, $data['name'], $data['description'] ?? null);

        return redirect()->to(route('modules.show', $session->course_module_id).'#seance-'.$session->id)
            ->with('status', 'Modèle « '.$data['name'].' » enregistré : il est proposé à l’ajout d’une séance.');
    }

    public function update(Request $request, SessionTemplate $template): RedirectResponse
    {
        abort_if($template->isProvided(), 403, 'Les modèles fournis ne sont pas modifiables : créez une séance depuis le modèle puis enregistrez-la comme modèle personnel.');
        $template->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]));

        return redirect()->route('templates.index')->with('status', 'Modèle mis à jour.');
    }

    public function destroy(SessionTemplate $template): RedirectResponse
    {
        abort_if($template->isProvided(), 403, 'Les modèles fournis ne peuvent pas être supprimés.');
        $template->delete();

        return redirect()->route('templates.index')->with('status', 'Modèle supprimé.');
    }
}
