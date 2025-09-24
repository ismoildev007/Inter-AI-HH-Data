<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApplicationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');
        if ($userIds->isEmpty()) {
            $userIds = User::factory(50)->create()->pluck('id');
        }
        $resumeIds = Resume::pluck('id');
        if ($resumeIds->isEmpty()) {
            // backfill some resumes if needed
            for ($i = 0; $i < 50; $i++) {
                $resumeIds->push(Resume::factory()->create(['user_id' => $userIds->random()])->id);
            }
        }

        // Create 100 applications referencing existing users/resumes
        for ($i = 0; $i < 100; $i++) {
            Application::factory()->create([
                'user_id' => $userIds->random(),
                // keep vacancy_id null to avoid unique(user_id,vacancy_id) collisions
                'vacancy_id' => null,
                'resume_id' => $resumeIds->random(),
            ]);
        }
    }
}

