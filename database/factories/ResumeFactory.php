<?php

namespace Database\Factories;

use App\Models\Resume;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resume>
 */
class ResumeFactory extends Factory
{
    protected $model = Resume::class;

    public function definition(): array
    {
        $title = $this->faker->jobTitle();
        $mime = $this->faker->randomElement(['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
        return [
            'title' => $title,
            'description' => $this->faker->sentence(10),
            'file_path' => null,
            'file_mime' => $mime,
            'file_size' => $this->faker->numberBetween(50_000, 500_000),
            'parsed_text' => null,
            'is_primary' => $this->faker->boolean(25),
        ];
    }
}

