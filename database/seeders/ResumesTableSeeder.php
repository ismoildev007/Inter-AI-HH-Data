<?php

namespace Database\Seeders;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResumesTableSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');
        if ($userIds->isEmpty()) {
            $userIds = User::factory(50)->create()->pluck('id');
        }

        // Create 100 resumes attached to random users
        for ($i = 0; $i < 100; $i++) {
            Resume::factory()->create([
                'user_id' => $userIds->random(),
            ]);
        }
    }
}

