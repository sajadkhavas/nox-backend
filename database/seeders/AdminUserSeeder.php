<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('LBB_DEV_ADMIN_EMAIL'));
        $password = (string) env('LBB_DEV_ADMIN_PASSWORD');

        if ($email === '' && $password === '') {
            $this->command?->warn('LBB development admin was not seeded; credentials are not configured.');

            return;
        }

        if ($email === '' || $password === '') {
            throw new RuntimeException(
                'Both LBB_DEV_ADMIN_EMAIL and LBB_DEV_ADMIN_PASSWORD are required to seed a development admin.',
            );
        }

        if (mb_strlen($password) < 16) {
            throw new RuntimeException('LBB development admin password must contain at least 16 characters.');
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('LBB_DEV_ADMIN_NAME', 'LBB Admin'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );
    }
}
