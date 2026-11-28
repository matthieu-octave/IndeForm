<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/** Un compte = un espace de travail, créé avec le compte. */
class CreateAccount
{
    public function __invoke(string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($name, $email, $password) {
            $workspace = Workspace::create(['name' => 'Espace de '.$name]);

            return User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'workspace_id' => $workspace->id,
            ]);
        });
    }
}
