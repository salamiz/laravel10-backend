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
            $nextAvailableAchievements = AchievementService::calculateNextAvailableAchievements($unlockedAchievements);

            // Current Badge, Next badge, and Remaining achievements for next badge
            list($currentBadge, $nextBadge, $remainingToUnlockNextBadge) = BadgeService::calculateBadgeProgress($user);

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
}

