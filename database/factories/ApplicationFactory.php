<?php

namespace Database\Factories;

use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending','approved','rejected']);
        return [
            'status' => $status,
            'match_score' => $this->faker->randomFloat(2, 10, 99),
            'submitted_at' => $this->faker->dateTimeBetween('-60 days', 'now'),
            'external_id' => 'APP-'.Str::upper(Str::random(10)),
            'notes' => $this->faker->boolean(30) ? $this->faker->sentence(8) : null,
            'hh_status' => $this->faker->boolean(30) ? $this->faker->randomElement(['NEW','IN_PROGRESS','DONE']) : null,
        ];
    }
}

