<?php

namespace App\Http\Controllers;

use App\Models\CourseModule;
use App\Models\SessionTemplate;
use App\Support\ModuleExchange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CourseModuleController extends Controller
{
    public function index(Request $request): View
    {
        $modules = CourseModule::search($request->query('q'))
            ->withCount('sessions')
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

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
        $module->load('sessions.steps', 'resources');

        return view('modules.show', [
            'module' => $module,
            'templates' => SessionTemplate::orderByRaw('workspace_id is not null')->orderBy('name')->get(),
            'otherModules' => CourseModule::whereKeyNot($module->id)->orderBy('title')->get(['id', 'title']),
        ]);
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

    /**
     * Ouvre un module à partir de sa référence (ex. /modules/ref/BC01-UA03), pour les liens depuis Form'Pilot.
     * Plusieurs modules portent la référence (copies par groupe) : on affiche la bibliothèque filtrée.
     */
    public function byReference(string $reference): RedirectResponse
    {
        $matches = CourseModule::where('reference', $reference)->orderBy('title')->get(['id']);

        return match ($matches->count()) {
            0 => redirect()->route('modules.index', ['q' => $reference])->with('status', 'Aucun module avec la référence « '.$reference.' » : créez-le ou vérifiez la référence.'),
            1 => redirect()->route('modules.show', $matches->first()),
            default => redirect()->route('modules.index', ['q' => $reference]),
        };
    }

    public function export(CourseModule $module): Response
    {
        $name = Str::slug(($module->reference ? $module->reference.'-' : '').$module->title) ?: 'module';

        return response()->json(ModuleExchange::export($module), 200, [
            'Content-Disposition' => 'attachment; filename="'.$name.'.formstudio.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:1024']], [
            'file.required' => 'Choisissez un fichier .json exporté depuis Form’Studio.',
            'file.max' => 'Le fichier ne doit pas dépasser 1 Mo.',
        ]);
        $payload = json_decode((string) file_get_contents($request->file('file')->getRealPath()), true);
        if (! is_array($payload)) {
            return back()->withErrors(['file' => 'Ce fichier n’est pas un JSON valide.']);
        }

        $module = ModuleExchange::import($payload);

        return redirect()->route('modules.show', $module)->with('status', 'Module importé dans votre bibliothèque.');
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
