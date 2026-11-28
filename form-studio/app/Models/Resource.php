<?php

namespace App\Models;

use App\Models\Scopes\ThroughParentScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Lien vers un support, un exercice ou un corrigé (Drive ou autre adresse https). */
class Resource extends Model
{
    use HasFactory;

    public const TYPES = [
        'support' => 'Support',
        'exercice' => 'Exercice',
        'corrige' => 'Corrigé',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new ThroughParentScope('module'));
    }

    protected $fillable = ['course_module_id', 'teaching_session_id', 'type', 'title', 'url', 'position'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TeachingSession::class, 'teaching_session_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function host(): string
    {
        $host = (string) parse_url($this->url, PHP_URL_HOST);

        return match (true) {
            str_ends_with($host, 'drive.google.com'), str_ends_with($host, 'docs.google.com') => 'Google Drive',
            str_ends_with($host, 'sharepoint.com'), str_ends_with($host, 'onedrive.live.com') => 'OneDrive',
            default => preg_replace('/^www\./', '', $host),
        };
    }
}
