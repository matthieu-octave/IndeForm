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
        $session->steps()->create($this->validated($request) + [
            'position' => SessionStep::nextPosition($session->id, 'teaching_session_id'),
        ]);

        return $this->back($session, 'Étape ajoutée.');
    }

    public function update(Request $request, SessionStep $step): RedirectResponse
    {
        $step->update($this->validated($request));

        return $this->back($step->session, 'Étape modifiée.');
    }

    public function move(Request $request, SessionStep $step): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];
        $step->move($direction === 'up' ? -1 : 1);

        return $this->back($step->session);
    }

    public function destroy(SessionStep $step): RedirectResponse
    {
        $session = $step->session;
        $step->delete();

        return $this->back($session, 'Étape supprimée.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'instructions' => ['nullable', 'string'],
        ]);
    }

    private function back(TeachingSession $session, ?string $status = null): RedirectResponse
    {
        $redirect = redirect()->to(route('modules.show', $session->course_module_id).'#seance-'.$session->id);

        return $status ? $redirect->with('status', $status) : $redirect;
    }
}
