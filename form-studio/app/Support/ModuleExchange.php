<?php

namespace App\Support;

use App\Models\CourseModule;
use App\Models\Resource;
use App\Rules\SafeUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Export / import d'un module au format JSON (sauvegarde et partage entre formateurs). */
class ModuleExchange
{
    public const FORMAT = 'formstudio-module';

    public const VERSION = 1;

    public static function export(CourseModule $module): array
    {
        $module->loadMissing('sessions.steps', 'resources');
        $link = fn (Resource $r) => $r->only(['type', 'title', 'url']);

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported_at' => now()->toIso8601String(),
            'module' => $module->only(['title', 'reference', 'objectives', 'prerequisites', 'keywords']) + [
                'resources' => $module->resources->whereNull('teaching_session_id')->map($link)->values()->all(),
                'sessions' => $module->sessions->map(fn ($s) => [
                    'title' => $s->title,
                    'objectives' => $s->objectives,
                    'steps' => $s->steps->map(fn ($st) => $st->only(['title', 'minutes', 'instructions']))->values()->all(),
                    'resources' => $module->resources->where('teaching_session_id', $s->id)->map($link)->values()->all(),
                ])->values()->all(),
            ],
        ];
    }

    /** @throws ValidationException */
    public static function import(array $payload): CourseModule
    {
        $types = Rule::in(array_keys(Resource::TYPES));
        $data = Validator::make($payload, [
            'format' => ['required', Rule::in([self::FORMAT])],
            'version' => ['required', 'integer', Rule::in([self::VERSION])],
            'module.title' => ['required', 'string', 'max:255'],
            'module.reference' => ['nullable', 'string', 'max:100'],
            'module.objectives' => ['nullable', 'string'],
            'module.prerequisites' => ['nullable', 'string'],
            'module.keywords' => ['nullable', 'string', 'max:255'],
            'module.resources' => ['array', 'max:200'],
            'module.resources.*.type' => ['required', $types],
            'module.resources.*.title' => ['required', 'string', 'max:255'],
            'module.resources.*.url' => ['required', 'string', 'max:2048', new SafeUrl],
            'module.sessions' => ['array', 'max:200'],
            'module.sessions.*.title' => ['required', 'string', 'max:255'],
            'module.sessions.*.objectives' => ['nullable', 'string'],
            'module.sessions.*.steps' => ['array', 'max:200'],
            'module.sessions.*.steps.*.title' => ['required', 'string', 'max:255'],
            'module.sessions.*.steps.*.minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'module.sessions.*.steps.*.instructions' => ['nullable', 'string'],
            'module.sessions.*.resources' => ['array', 'max:200'],
            'module.sessions.*.resources.*.type' => ['required', $types],
            'module.sessions.*.resources.*.title' => ['required', 'string', 'max:255'],
            'module.sessions.*.resources.*.url' => ['required', 'string', 'max:2048', new SafeUrl],
        ], [
            'format.*' => 'Ce fichier n’est pas un export de module Form’Studio.',
            'version.*' => 'Version d’export non prise en charge.',
        ])->validate()['module'];

        return DB::transaction(function () use ($data) {
            $module = CourseModule::create(collect($data)->only(['title', 'reference', 'objectives', 'prerequisites', 'keywords'])->all());
            $position = 0;
            foreach ($data['resources'] ?? [] as $r) {
                $module->resources()->create($r + ['position' => ++$position]);
            }
            foreach (array_values($data['sessions'] ?? []) as $i => $s) {
                $session = $module->sessions()->create(['title' => $s['title'], 'objectives' => $s['objectives'] ?? null, 'position' => $i + 1]);
                foreach (array_values($s['steps'] ?? []) as $j => $st) {
                    $session->steps()->create([
                        'title' => $st['title'], 'minutes' => $st['minutes'],
                        'instructions' => $st['instructions'] ?? null, 'position' => $j + 1,
                    ]);
                }
                foreach ($s['resources'] ?? [] as $r) {
                    $module->resources()->create($r + ['teaching_session_id' => $session->id, 'position' => ++$position]);
                }
            }

            return $module;
        });
    }
}
