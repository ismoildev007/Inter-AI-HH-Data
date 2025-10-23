<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        Subscription::query()->delete();

        if (Plan::count() === 0) {
            $this->call(PlanSeeder::class);
        }

        $requiredUsers = 100;
        $existingUsers = User::count();

        if ($existingUsers < $requiredUsers) {
            User::factory()->count($requiredUsers - $existingUsers)->create();
        }

        $plans = Plan::all();
        $users = User::pluck('id')->all();
        $statuses = ['active', 'pending', 'expired', 'cancelled'];

        for ($i = 0; $i < 100; $i++) {
            $plan = $plans->random();
            $start = Carbon::now()->subDays($faker->numberBetween(0, 120));
            $end = (clone $start)->addDays($faker->numberBetween(15, 120));

            $limit = max($plan->auto_response_limit, 1);

            Subscription::create([
                'user_id' => $faker->randomElement($users),
                'plan_id' => $plan->id,
                'starts_at' => $start,
                'ends_at' => $end,
                'remaining_auto_responses' => $faker->numberBetween(0, $limit),
                'status' => $faker->randomElement($statuses),
            ]);
        }
    }
}
