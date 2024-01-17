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

    public static function calculateNextAvailableAchievements(array $unlockedAchievements)
    {
        $allAchievements = self::getAchievements(); // Add this line to call the static method

        $nextAvailableAchievements = [];

        // Users with no achievements
        if (empty($unlockedAchievements)) {
            foreach ($allAchievements as $achievements) {
                $nextAvailableAchievements[] = reset($achievements); // Get the first achievement from each category
            }
            return $nextAvailableAchievements;
        }

        // Users with some achievements
        foreach ($allAchievements as $category => $achievements) {
            foreach ($achievements as $achievement) {
                if (!in_array($achievement, $unlockedAchievements)) {
                    $nextAvailableAchievements[] = $achievement;
                    break;
                }
            }
        }

        return $nextAvailableAchievements;
    }
}
