<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Identifiant public (UUID v7, ordonné dans le temps) généré par le socle.
 * C'est le seul identifiant qui sort du socle : les modules le stockent et ne l'inventent jamais.
 * L'identifiant entier interne (id) reste privé.
 */
trait HasPublicUuid
{
    public static function bootHasPublicUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
