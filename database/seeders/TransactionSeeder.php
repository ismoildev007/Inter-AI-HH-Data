<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        Transaction::query()->delete();

        if (Plan::count() === 0) {
            $this->call(PlanSeeder::class);
        }

        if (Subscription::count() < 100) {
            $this->call(SubscriptionSeeder::class);
        }

        $requiredUsers = 100;
        $existingUsers = User::count();

        if ($existingUsers < $requiredUsers) {
            User::factory()->count($requiredUsers - $existingUsers)->create();
        }

        $plans = Plan::all()->keyBy('id');
        $subscriptions = Subscription::all();
        $users = User::pluck('id')->all();
        $states = [0, 1, 2, -1, -2];
        $hasPlanIdColumn = Schema::hasColumn('transactions', 'plan_id');

        for ($i = 0; $i < 100; $i++) {
            $state = $faker->randomElement($states);
            $subscription = null;

            if ($subscriptions->isNotEmpty() && $faker->boolean(70)) {
                $subscription = $subscriptions->random();
            }

            $plan = $subscription ? $plans->get($subscription->plan_id) : null;
            if (!$plan) {
                $plan = $plans->random();
            }

            $rawPlanPrice = $plan?->getRawOriginal('price');
            $amount = $faker->numberBetween(50_000, 400_000);
            if ($rawPlanPrice !== null && $rawPlanPrice !== '') {
                $amount = (int) round((float) $rawPlanPrice);
            }
            $createdAt = Carbon::now()
                ->subDays($faker->numberBetween(0, 90))
                ->subMinutes($faker->numberBetween(0, 1_440));

            $performAt = null;
            $cancelAt = null;
            $paymentStatus = 'pending';
            $reason = null;

            if ($state === 1) {
                $paymentStatus = 'completed';
                $performAt = (clone $createdAt)->addMinutes($faker->numberBetween(1, 120));
            } elseif ($state === 2) {
                $paymentStatus = 'cancelled';
                $cancelAt = (clone $createdAt)->addMinutes($faker->numberBetween(1, 240));
                $reason = $faker->numberBetween(1, 5);
            } elseif ($state < 0) {
                $paymentStatus = 'failed';
                $cancelAt = (clone $createdAt)->addMinutes($faker->numberBetween(1, 180));
                $reason = $faker->numberBetween(100, 999);
            }

            $data = [
                'user_id' => $faker->randomElement($users),
                'subscription_id' => $subscription?->id,
                'payment_status' => $paymentStatus,
                'transaction_id' => Str::uuid()->toString(),
                'payment_method' => $faker->randomElement(['payme', 'click']),
                'state' => $state,
                'amount' => $amount,
                'create_time' => $createdAt->getTimestamp(),
                'perform_time' => $performAt?->getTimestamp(),
                'cancel_time' => $cancelAt?->getTimestamp(),
                'reason' => $reason,
            ];

            if ($hasPlanIdColumn && $plan) {
                $data['plan_id'] = $plan->id;
            }

            Transaction::create($data);
        }
    }
}
