<?php

namespace App\Http\Controllers;

use App\Models\SessionStep;
use App\Models\TeachingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionStepController extends Controller
{
    public function store(Request $request, TeachingSession $session): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'instructions' => ['nullable', 'string'],
        ]);

        $session->steps()->create($data + ['position' => ((int) $session->steps()->max('position')) + 1]);

        return redirect()->route('modules.show', $session->course_module_id)->with('status', 'Étape ajoutée.');
    }

    public function destroy(SessionStep $step): RedirectResponse
    {
        $moduleId = $step->session->course_module_id;
        $step->delete();

        return redirect()->route('modules.show', $moduleId)->with('status', 'Étape supprimée.');
    }
}
