<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/*
 * DDE-Mart Admin — initial staff seed (original seeder).
 * Creates the protected Super Admin role + first admin login.
 * Idempotent: safe to re-run. Change the default password immediately.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::updateOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_super' => true],
        );

        User::updateOrCreate(
            ['email' => 'admin@email.com'],
            [
                'name' => 'DDE-Mart Admin',
                'password' => '12345678',
                'role_id' => $role->id,
                'email_verified_at' => now(),
            ],
        );
    }
}
