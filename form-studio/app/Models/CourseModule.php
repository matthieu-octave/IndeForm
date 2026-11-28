<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model
{
    use BelongsToWorkspace, HasFactory;

    protected $fillable = ['title', 'reference', 'objectives', 'prerequisites', 'keywords', 'duplicated_from_id'];

    public function sessions(): HasMany
    {
        return $this->hasMany(TeachingSession::class)->orderBy('position')->orderBy('id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class)->orderBy('position')->orderBy('id');
    }

    /** Liens rattachés au module lui-même (pas à une séance). */
    public function moduleResources(): HasMany
    {
        return $this->resources()->whereNull('teaching_session_id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(fn (Builder $q) => $q
            ->where('title', 'like', $like)
            ->orWhere('reference', 'like', $like)
            ->orWhere('keywords', 'like', $like));
    }

    public function totalMinutes(): int
    {
        return (int) SessionStep::whereIn('teaching_session_id', $this->sessions()->pluck('id'))->sum('minutes');
    }

    /** Copie indépendante du module, avec ses séances et ses étapes. */
    public function duplicate(?string $title = null): self
    {
        $copy = $this->replicate(['created_at', 'updated_at']);
        $copy->title = $title ?: $this->title.' (copie)';
        $copy->duplicated_from_id = $this->id;
        $copy->save();

        $sessionMap = [];
        foreach ($this->sessions()->with('steps')->get() as $session) {
            $newSession = $session->replicate(['created_at', 'updated_at']);
            $newSession->course_module_id = $copy->id;
            $newSession->notes = null;
            $newSession->save();
            $sessionMap[$session->id] = $newSession->id;
            foreach ($session->steps as $step) {
                $newSession->steps()->create($step->only(['position', 'title', 'minutes', 'instructions']));
            }
        }

        foreach ($this->resources as $resource) {
            $copy->resources()->create($resource->only(['type', 'title', 'url', 'position']) + [
                'teaching_session_id' => $sessionMap[$resource->teaching_session_id] ?? null,
            ]);
        }

        return $copy;
    }
}
