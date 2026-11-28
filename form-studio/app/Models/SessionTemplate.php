<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Modèle de séance : un déroulé type à copier dans une nouvelle séance.
 * workspace_id NULL = modèle fourni, commun à tous les comptes et non modifiable.
 */
class SessionTemplate extends Model
{
    protected $fillable = ['workspace_id', 'name', 'description', 'steps'];

    protected function casts(): array
    {
        return ['steps' => 'array'];
    }

    protected static function booted(): void
    {
        // Visibles : les modèles fournis et ceux de son propre espace.
        static::addGlobalScope('visible', function (Builder $query) {
            $workspaceId = Auth::user()?->workspace_id;
            $query->where(fn (Builder $q) => $q
                ->whereNull('session_templates.workspace_id')
                ->when($workspaceId, fn (Builder $q) => $q->orWhere('session_templates.workspace_id', $workspaceId)));
        });
    }

    public function isProvided(): bool
    {
        return $this->workspace_id === null;
    }

    public function totalMinutes(): int
    {
        return (int) array_sum(array_column($this->steps ?? [], 'minutes'));
    }

    /** Crée une séance indépendante à la fin du module, avec le déroulé du modèle. */
    public function createSessionIn(CourseModule $module, ?string $title = null, ?string $objectives = null): TeachingSession
    {
        $session = $module->sessions()->create([
            'title' => $title ?: $this->name,
            'objectives' => $objectives,
            'position' => TeachingSession::nextPosition($module->id, 'course_module_id'),
        ]);

        foreach (array_values($this->steps ?? []) as $i => $step) {
            $session->steps()->create([
                'position' => $i + 1,
                'title' => $step['title'],
                'minutes' => (int) $step['minutes'],
                'instructions' => $step['instructions'] ?? null,
            ]);
        }

        return $session;
    }

    public static function fromSession(TeachingSession $session, string $name, ?string $description = null): self
    {
        return static::create([
            'workspace_id' => Auth::user()->workspace_id,
            'name' => $name,
            'description' => $description,
            'steps' => $session->steps->map(fn (SessionStep $s) => $s->only(['title', 'minutes', 'instructions']))->values()->all(),
        ]);
    }
}
