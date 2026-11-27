<?php

namespace Database\Factories;

use App\Models\TeachingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class SessionStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'teaching_session_id' => TeachingSession::factory(),
            'position' => 1,
            'title' => fake()->words(3, true),
            'minutes' => 30,
        ];
    }
}
