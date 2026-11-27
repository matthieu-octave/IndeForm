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

        $module->sessions()->create($data + ['position' => ((int) $module->sessions()->max('position')) + 1]);

        return redirect()->route('modules.show', $module)->with('status', 'Séance ajoutée.');
    }

    public function update(Request $request, TeachingSession $session): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'objectives' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $session->update($data);

        return redirect()->route('modules.show', $session->course_module_id)->with('status', 'Séance mise à jour.');
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
}
