<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower((string) config('services.teachshare.admin_email'));
        $password = env('TEACHSHARE_ADMIN_PASSWORD') ?: Str::password(20);
        $generated = ! env('TEACHSHARE_ADMIN_PASSWORD');

        $admin = User::firstOrNew(['email' => $email]);
        if ($admin->exists) {
            $this->command?->warn("Admin {$email} already exists; left unchanged.");

            return;
        }

        // role is not mass-assignable on purpose, so set it explicitly.
        $admin->forceFill([
            'name' => 'TeachShare Admin',
            'password' => Hash::make($password),
            'role' => 'admin',
            'email_verified_at' => now(),
        ])->save();

        $this->command?->info("Admin created: {$email}");
        if ($generated) {
            $this->command?->warn("Generated password (shown once): {$password}");
        }
    }
}
