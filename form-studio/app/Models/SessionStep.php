<?php

namespace App\Models;

use App\Models\Concerns\HasPosition;
use App\Models\Scopes\ThroughParentScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionStep extends Model
{
    use HasFactory, HasPosition;

    protected static function booted(): void
    {
        static::addGlobalScope(new ThroughParentScope('session'));
    }

    protected $fillable = ['teaching_session_id', 'position', 'title', 'minutes', 'instructions'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TeachingSession::class, 'teaching_session_id');
    }

    protected function positionParentKey(): string
    {
        return 'teaching_session_id';
    }
}
