<?php

namespace Database\Factories;

use App\Models\CourseModule;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeachingSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_module_id' => CourseModule::factory(),
            'position' => 1,
            'title' => fake()->words(3, true),
        ];
    }
}
