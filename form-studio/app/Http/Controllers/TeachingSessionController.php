<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\SessionTemplate;
use App\Models\TeachingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeachingSessionController extends Controller
{
    public function store(Request $request, CourseModule $module): RedirectResponse
    {
        $data = $request->validate([
            'template_id' => ['nullable', 'integer'],
            'title' => [$request->filled('template_id') ? 'nullable' : 'required', 'string', 'max:255'],
            'objectives' => ['nullable', 'string'],
        ]);

        if (! empty($data['template_id'])) {
            // La règle de visibilité empêche d'utiliser le modèle personnel d'un autre compte.
            $template = SessionTemplate::findOrFail($data['template_id']);
            $session = $template->createSessionIn($module, $data['title'] ?? null, $data['objectives'] ?? null);

            return $this->back($session, 'Séance créée depuis le modèle « '.$template->name.' » : adaptez-la librement.');
        }

        $session = $module->sessions()->create([
            'title' => $data['title'],
            'objectives' => $data['objectives'] ?? null,
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

    public function present(Request $request, TeachingSession $session): View
    {
        $session->load('module', 'steps', 'resources');
        $start = $request->query('debut');
        if (! is_string($start) || ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $start)) {
            $start = null;
        }

        return view('sessions.present', ['session' => $session, 'start' => $start]);
    }

    /** Enregistre les durées réellement constatées (saisies ou relevées par le chronomètre). */
    public function saveActuals(Request $request, TeachingSession $session): RedirectResponse
    {
        $data = $request->validate([
            'actual' => ['array'],
            'actual.*' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'reset' => ['nullable', 'boolean'],
        ]);

        foreach ($session->steps as $step) {
            $value = $request->boolean('reset') ? null : ($data['actual'][$step->id] ?? null);
            $step->update(['actual_minutes' => $value === null || $value === '' ? null : (int) $value]);
        }

        return redirect()->back()->with('status', $request->boolean('reset') ? 'Durées réelles effacées.' : 'Durées réelles enregistrées.');
    }

    private function back(TeachingSession $session, ?string $status = null): RedirectResponse
    {
        $redirect = redirect()->to(route('modules.show', $session->course_module_id).'#seance-'.$session->id);

        return $status ? $redirect->with('status', $status) : $redirect;
    }
}
