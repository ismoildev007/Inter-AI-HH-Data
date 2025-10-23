<?php

namespace Database\Seeders;

use App\Models\Plan;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        Plan::query()->delete();

        $faker->unique(true);

        for ($i = 1; $i <= 100; $i++) {
            $price = $faker->numberBetween(50_000, 400_000);
            $fakePrice = $price + $faker->numberBetween(10_000, 150_000);

            Plan::create([
                'name' => sprintf('%s Plan %d', Str::title($faker->unique()->words(2, true)), $i),
                'description' => $faker->paragraph(),
                'fake_price' => $fakePrice,
                'price' => $price,
                'auto_response_limit' => $faker->numberBetween(10, 250),
                'duration' => Carbon::now()->addDays($faker->numberBetween(30, 365)),
            ]);
        }
    }
}
