<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_tidak_membuat_demo_user(): void
    {
        $this->app->instance('env', 'production');

        (new RoleSeeder)->run();
        (new DemoUserSeeder)->run();

        $this->assertTrue(Role::where('name', 'SuperAdmin')->exists());
        $this->assertDatabaseMissing('users', ['nik' => 'SA001']);
        $this->assertDatabaseMissing('users', ['nik' => 'ME001']);
    }

    public function test_demo_seeder_tidak_meriset_password_user_yang_sudah_ada(): void
    {
        (new RoleSeeder)->run();

        $existing = User::create([
            'nik' => 'ME001',
            'name' => 'Mekanik Asli',
            'password' => Hash::make('password-lama'),
        ]);

        $this->app->instance('env', 'local');
        (new DemoUserSeeder)->run();

        $this->assertTrue(Hash::check('password-lama', $existing->fresh()->password));
    }
}
