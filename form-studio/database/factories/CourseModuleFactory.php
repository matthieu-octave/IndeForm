<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CourseModuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => \App\Models\Workspace::factory(),
            'title' => fake()->words(3, true),
            'reference' => null,
        ];
    }
}
