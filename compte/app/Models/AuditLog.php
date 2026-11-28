<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Journal d'audit (connexions, mots de passe, e-mails, créations de comptes). */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /** Enregistre une action, avec l'adresse IP et le navigateur de la requête en cours s'il y en a une. */
    public static function record(string $action, ?User $user = null, ?Model $subject = null): self
    {
        $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

        return static::create([
            'action' => $action,
            'user_id' => $user?->id,
            'workspace_id' => $user?->current_workspace_id,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_uuid' => $subject?->uuid,
            'ip' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 500) : null,
        ]);
    }
}
