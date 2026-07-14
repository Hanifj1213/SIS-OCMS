<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'SuperAdmin',
            'Mechanic',
            'Supervisor',
            'QC Inspector',
            'Planner/Warehouse',
            'Management',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // === Demo Users untuk setiap role ===

        $users = [
            [
                'nik'  => 'SA001',
                'name' => 'Admin IT',
                'role' => 'SuperAdmin',
            ],
            [
                'nik'  => 'ME001',
                'name' => 'Budi Mekanik',
                'role' => 'Mechanic',
            ],
            [
                'nik'  => 'SP001',
                'name' => 'Andi Supervisor',
                'role' => 'Supervisor',
            ],
            [
                'nik'  => 'QC001',
                'name' => 'Rina QC Inspector',
                'role' => 'QC Inspector',
            ],
            [
                'nik'  => 'PW001',
                'name' => 'Deni Gudang',
                'role' => 'Planner/Warehouse',
            ],
            [
                'nik'  => 'MG001',
                'name' => 'Pak Direktur',
                'role' => 'Management',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['nik' => $userData['nik']],
                [
                    'name'     => $userData['name'],
                    'password' => Hash::make('password'),
                ]
            );
            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }
    }
}
