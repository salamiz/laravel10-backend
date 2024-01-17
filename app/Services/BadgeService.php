<?php

namespace App\Services;

use App\Models\User;

class BadgeService
{
    public static function getBadges()
    {
        return [
            'Beginner' => 0,
            'Intermediate' => 4,
            'Advanced' => 8,
            'Master' => 10
        ];
    }

    public static function calculateBadgeProgress(User $user)
    {
        $achievementCount = $user->achievements()->count();
        $badges = BadgeService::getBadges();
        $currentBadge = 'Beginner'; // Default badge
        $nextBadge = '';
        $remainingToUnlockNextBadge = 0;

        foreach ($badges as $badge => $threshold) {
            if ($achievementCount < $threshold) {
                $nextBadge = $badge;
                $remainingToUnlockNextBadge = $threshold - $achievementCount;
                break;
            }
            $currentBadge = $badge;
        }

        return [$currentBadge, $nextBadge, $remainingToUnlockNextBadge];
    }
}
