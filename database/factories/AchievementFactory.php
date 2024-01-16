<?php

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AchievementFactory extends Factory
{
    protected $model = Achievement::class;

    public function definition()
    {
        $achievementNames = [
            'First Lesson Watched',
            '5 Lessons Watched',
            '10 Lessons Watched',
            '25 Lessons Watched',
            '50 Lessons Watched',
            'First Comment Written',
            '3 Comments Written',
            '5 Comments Written',
            '10 Comments Written',
            '20 Comments Written'
        ];

        return [
            'name' => $this->faker->randomElement($achievementNames),
        ];
    }
}
