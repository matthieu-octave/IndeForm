<?php

namespace App\Models;

use App\Models\Scopes\ThroughParentScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingSession extends Model
{
    use HasFactory;

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

    public function totalMinutes(): int
    {
        return (int) $this->steps()->sum('minutes');
    }
}
