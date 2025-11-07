<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the admin role exists
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $email = config('admin.seeder.email', 'admin@inter.ai');
        $password = config('admin.seeder.password', '#p-hSca4wtEG6TmBaxromaka007');

        // Create or update the admin user
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make($password),
                'role_id' => $adminRole->id,
            ]
        );

        // Ensure role_id is set correctly if the user already existed
        if ($user->role_id !== $adminRole->id) {
            $user->role_id = $adminRole->id;
            $user->save();
        }

        // Mark email as verified if not set
        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
            $user->save();
        }
    }
}
