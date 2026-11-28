<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/** Un compte = un espace de travail, créé avec le compte (l'utilisateur en est propriétaire). */
class CreateAccount
{
    public function __invoke(string $name, string $email, ?string $password, string $accountType = 'customer', bool $verified = false): User
    {
        return DB::transaction(function () use ($name, $email, $password, $accountType, $verified) {
            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->account_type = $accountType;
            $user->email_verified_at = $verified ? now() : null;
            $user->save();

            $workspace = new Workspace(['name' => $name]);
            $workspace->owner_user_id = $user->id;
            $workspace->save();
            $workspace->users()->attach($user->id, ['role' => 'owner']);

            $user->current_workspace_id = $workspace->id;
            $user->save();

            AuditLog::record('account.created', $user, $user);

            return $user;
        });
    }
}
