<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\OcmsAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun demo untuk local/testing saja. Jangan jalankan di production.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->shouldSeed()) {
            return;
        }

        $users = [
            ['nik' => 'SA001', 'name' => 'Rizky Pratama', 'role' => OcmsAccess::ROLE_SUPER_ADMIN],
            ['nik' => 'DEV001', 'name' => 'Developer OCMS', 'role' => OcmsAccess::ROLE_DEVELOPER],
            ['nik' => 'ME001', 'name' => 'Budi Santoso', 'role' => OcmsAccess::ROLE_MECHANIC],
            ['nik' => 'GL001', 'name' => 'Andi Wijaya', 'role' => OcmsAccess::ROLE_GROUP_LEADER],
            ['nik' => 'SP001', 'name' => 'Joko Susilo', 'role' => OcmsAccess::ROLE_SUPERVISOR],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['nik' => $userData['nik']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                ],
            );

            if (! $user->wasRecentlyCreated) {
                continue;
            }

            $user->syncRoles([$userData['role']]);
        }
    }

    private function shouldSeed(): bool
    {
        if (app()->environment('local', 'testing')) {
            return true;
        }

        return filter_var(env('OCMS_SEED_DEMO_USERS', false), FILTER_VALIDATE_BOOL);
    }
}
