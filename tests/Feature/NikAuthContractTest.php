<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NikAuthContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_nik_berhasil(): void
    {
        $user = User::create([
            'nik' => 'NIK-9001',
            'name' => 'Tester NIK',
            'password' => Hash::make('SecretPass123'),
        ]);

        $response = $this->post('/login', [
            'nik' => 'nik-9001',
            'password' => 'SecretPass123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_forgot_password_route_tidak_tersedia(): void
    {
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_verify_email_route_tidak_tersedia(): void
    {
        $user = User::create([
            'nik' => 'NIK-9002',
            'name' => 'No Email',
            'password' => 'password',
        ]);

        $this->actingAs($user)->get('/verify-email')->assertNotFound();
    }
}
