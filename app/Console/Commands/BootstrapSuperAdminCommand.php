<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\OcmsAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class BootstrapSuperAdminCommand extends Command
{
    protected $signature = 'ocms:bootstrap-superadmin
                            {--nik= : NIK SuperAdmin}
                            {--name= : Nama lengkap}';

    protected $description = 'Buat SuperAdmin produksi (interaktif, password tidak dicetak)';

    public function handle(): int
    {
        $nik = strtoupper(trim((string) ($this->option('nik') ?: $this->ask('NIK SuperAdmin'))));
        $name = trim((string) ($this->option('name') ?: $this->ask('Nama lengkap')));

        if ($nik === '' || $name === '') {
            $this->error('NIK dan nama wajib diisi.');

            return 1;
        }

        if (User::where('nik', $nik)->exists()) {
            $this->error("User dengan NIK {$nik} sudah ada.");

            return 1;
        }

        $password = (string) $this->secret('Password SuperAdmin (min 12 karakter)');
        $confirm = (string) $this->secret('Konfirmasi password');

        if ($password !== $confirm) {
            $this->error('Konfirmasi password tidak cocok.');

            return 1;
        }

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', 'min:12', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/']],
            ['password.regex' => 'Password harus mengandung huruf kecil, huruf besar, dan angka.'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return 1;
        }

        Role::firstOrCreate(['name' => OcmsAccess::ROLE_SUPER_ADMIN, 'guard_name' => 'web']);

        $user = User::create([
            'nik' => $nik,
            'name' => $name,
            'password' => Hash::make($password),
        ]);

        $user->assignRole(OcmsAccess::ROLE_SUPER_ADMIN);

        $this->info("SuperAdmin {$nik} berhasil dibuat. Password tidak ditampilkan — simpan dengan aman.");

        return 0;
    }
}
