<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AchievementService;
use App\Services\BadgeService;
use Illuminate\Support\Facades\Log;

class AchievementsController extends Controller
{
    public function index(User $user)
    {
        try{
            // Unlocked achievements
            $unlockedAchievements = $user->achievements()->pluck('name')->toArray();

            // Next available achievements
            $nextAvailableAchievements = $this->calculateNextAvailableAchievements($unlockedAchievements);

            // Current Badge, Next badge, and Remaining achievements for next badge
            list($currentBadge, $nextBadge, $remainingToUnlockNextBadge) = $this->calculateBadgeProgress($user);

            return response()->json([
                'unlocked_achievements' => $unlockedAchievements,
                'next_available_achievements' => $nextAvailableAchievements,
                'current_badge' => $currentBadge,
                'next_badge' => $nextBadge,
                'remaining_to_unlock_next_badge' => $remainingToUnlockNextBadge
            ]);
        }catch (\Exception $e) {
            // Log the error
            Log::error('Error in AchievementsController: ' . $e->getMessage());
    
            // Return an error response
            return response()->json(['error' => 'An error occurred while processing your request.'], 500);
        }
    }

    private function calculateNextAvailableAchievements(array $unlockedAchievements)
    {
        // Logic to calculate next available achievements based on current unlocked achievements
        $allAchievements = AchievementService::getAchievements();
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

    private function calculateBadgeProgress(User $user)
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

