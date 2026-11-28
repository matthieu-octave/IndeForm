<?php

namespace Database\Seeders;

use App\Actions\CreateAccount;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Compte de démonstration en local : demo@example.test / mot-de-passe-demo. */
    public function run(CreateAccount $createAccount): void
    {
        $createAccount('Compte de démonstration', 'demo@example.test', 'mot-de-passe-demo', 'internal', verified: true);
    }
}
