<?php

namespace Database\Factories;

use App\Models\CourseModule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_module_id' => CourseModule::factory(),
            'type' => 'support',
            'title' => fake()->words(3, true),
            'url' => 'https://drive.google.com/file/d/'.fake()->uuid().'/view',
            'position' => 1,
        ];
    }
}
