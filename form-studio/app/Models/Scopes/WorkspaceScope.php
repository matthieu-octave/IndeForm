<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/** Ne montre que les données de l'espace de travail de l'utilisateur connecté (rien sans session). */
class WorkspaceScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $workspaceId = Auth::user()?->workspace_id;

        $workspaceId === null
            ? $builder->whereRaw('1 = 0')
            : $builder->where($model->getTable().'.workspace_id', $workspaceId);
    }
}
