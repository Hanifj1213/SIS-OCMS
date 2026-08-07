<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * @deprecated Gunakan RoleSeeder + DemoUserSeeder. Dipertahankan untuk kompatibilitas.
 */
class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DemoUserSeeder::class,
        ]);
    }
}
