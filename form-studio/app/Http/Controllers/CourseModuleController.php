<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseModuleController extends Controller
{
    public function index(Request $request): View
    {
        $modules = CourseModule::search($request->query('q'))
            ->withCount('sessions')
            ->orderBy('title')
            ->get();

        return view('modules.index', ['modules' => $modules, 'q' => $request->query('q')]);
    }

    public function create(): View
    {
        return view('modules.form', ['module' => new CourseModule]);
    }

    public function store(Request $request): RedirectResponse
    {
        $module = CourseModule::create($this->validated($request));

        return redirect()->route('modules.show', $module)->with('status', 'Module créé.');
    }

    public function show(CourseModule $module): View
    {
        $module->load('sessions.steps');

        return view('modules.show', ['module' => $module]);
    }

    public function edit(CourseModule $module): View
    {
        return view('modules.form', ['module' => $module]);
    }

    public function update(Request $request, CourseModule $module): RedirectResponse
    {
        $module->update($this->validated($request));

        return redirect()->route('modules.show', $module)->with('status', 'Module mis à jour.');
    }

    public function destroy(CourseModule $module): RedirectResponse
    {
        $module->delete();

        return redirect()->route('modules.index')->with('status', 'Module supprimé.');
    }

    public function duplicate(Request $request, CourseModule $module): RedirectResponse
    {
        $data = $request->validate(['title' => ['nullable', 'string', 'max:255']]);
        $copy = $module->duplicate($data['title'] ?? null);

        return redirect()->route('modules.show', $copy)->with('status', 'Copie créée : elle évolue indépendamment de l’original.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'objectives' => ['nullable', 'string'],
            'prerequisites' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
