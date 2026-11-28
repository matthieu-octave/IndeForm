<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\Resource;
use App\Rules\SafeUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResourceController extends Controller
{
    public function store(Request $request, CourseModule $module): RedirectResponse
    {
        $data = $this->validated($request, $module);
        $module->resources()->create($data + [
            'position' => ((int) $module->resources()->max('position')) + 1,
        ]);

        return $this->back($module, $data['teaching_session_id'] ?? null, 'Lien ajouté.');
    }

    public function update(Request $request, Resource $resource): RedirectResponse
    {
        $data = $this->validated($request, $resource->module);
        $resource->update($data);

        return $this->back($resource->module, $resource->teaching_session_id, 'Lien modifié.');
    }

    public function destroy(Resource $resource): RedirectResponse
    {
        $resource->delete();

        return $this->back($resource->module, $resource->teaching_session_id, 'Lien supprimé.');
    }

    private function validated(Request $request, CourseModule $module): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Resource::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:2048', new SafeUrl],
            // La séance doit appartenir au même module.
            'teaching_session_id' => ['nullable', 'integer', Rule::in($module->sessions()->pluck('id')->all())],
        ]);
        $data['url'] = trim($data['url']);

        return $data;
    }

    private function back(CourseModule $module, ?int $sessionId, string $status): RedirectResponse
    {
        $anchor = $sessionId ? '#seance-'.$sessionId : '#ressources';

        return redirect()->to(route('modules.show', $module).$anchor)->with('status', $status);
    }
}
