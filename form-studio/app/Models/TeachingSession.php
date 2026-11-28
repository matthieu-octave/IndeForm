<?php

namespace App\Models;

use App\Models\Concerns\HasPosition;
use App\Models\Scopes\ThroughParentScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingSession extends Model
{
    use HasFactory, HasPosition;

    protected static function booted(): void
    {
        static::addGlobalScope(new ThroughParentScope('module'));
    }

    protected $fillable = ['course_module_id', 'position', 'title', 'objectives', 'notes'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(SessionStep::class)->orderBy('position')->orderBy('id');
    }

    /** Copie indépendante de la séance (étapes comprises, sans les notes) à la fin du module cible. */
    public function duplicateInto(CourseModule $module, ?string $title = null): self
    {
        $copy = $this->replicate(['created_at', 'updated_at']);
        $copy->course_module_id = $module->id;
        $copy->position = static::nextPosition($module->id, 'course_module_id');
        $copy->title = $title ?: $this->title;
        $copy->notes = null;
        $copy->save();

        foreach ($this->steps as $step) {
            $copy->steps()->create($step->only(['position', 'title', 'minutes', 'instructions']));
        }
        foreach ($this->resources as $resource) {
            $copy->resources()->create($resource->only(['type', 'title', 'url', 'position']) + [
                'course_module_id' => $module->id,
            ]);
        }

        return $copy;
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class)->orderBy('position')->orderBy('id');
    }

    public function totalMinutes(): int
    {
        return (int) $this->steps()->sum('minutes');
    }

    protected function positionParentKey(): string
    {
        return 'course_module_id';
    }
}
