<?php

namespace App\Services;

class AchievementService
{
    public static function getAchievements()
    {
        return [
            'Lessons Watched' => ['First Lesson Watched', '5 Lessons Watched', '10 Lessons Watched', '25 Lessons Watched', '50 Lessons Watched'],
            'Comments Written' => ['First Comment Written', '3 Comments Written', '5 Comments Written', '10 Comments Written', '20 Comments Written'],
        ];
    }
}
