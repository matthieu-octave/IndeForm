<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** Limite un modèle enfant aux lignes dont le parent est visible (donc du même espace de travail). */
class ThroughParentScope implements Scope
{
    public function __construct(private string $relation) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas($this->relation);
    }
}
