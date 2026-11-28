<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\TeachingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeachingSessionController extends Controller
{
    public function store(Request $request, CourseModule $module): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'objectives' => ['nullable', 'string'],
        ]);

        $session = $module->sessions()->create($data + [
            'position' => TeachingSession::nextPosition($module->id, 'course_module_id'),
        ]);

        return $this->back($session, 'Séance ajoutée.');
    }

    public function update(Request $request, TeachingSession $session): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'objectives' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $session->update($data);

        return $this->back($session, 'Séance mise à jour.');
    }

    public function move(Request $request, TeachingSession $session): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];
        $session->move($direction === 'up' ? -1 : 1);

        return $this->back($session);
    }

    /** Copie une séance seule, dans le même module ou dans un autre module de l'espace. */
    public function duplicate(Request $request, TeachingSession $session): RedirectResponse
    {
        $data = $request->validate([
            'course_module_id' => ['nullable', 'integer'],
        ]);
        // findOrFail passe par la règle d'espace : impossible de copier vers le module d'un autre compte.
        $target = isset($data['course_module_id'])
            ? CourseModule::findOrFail($data['course_module_id'])
            : $session->module;

        $copy = $session->duplicateInto($target, $target->is($session->module) ? $session->title.' (copie)' : null);

        return $this->back($copy, 'Séance copiée : elle évolue indépendamment de l’originale.');
    }

    public function destroy(TeachingSession $session): RedirectResponse
    {
        $moduleId = $session->course_module_id;
        $session->delete();

        return redirect()->route('modules.show', $moduleId)->with('status', 'Séance supprimée.');
    }

    public function present(TeachingSession $session): View
    {
        $session->load('module', 'steps');

        return view('sessions.present', ['session' => $session]);
    }

    private function back(TeachingSession $session, ?string $status = null): RedirectResponse
    {
        $redirect = redirect()->to(route('modules.show', $session->course_module_id).'#seance-'.$session->id);

        return $status ? $redirect->with('status', $status) : $redirect;
    }
}
