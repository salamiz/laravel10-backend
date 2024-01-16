<?php

namespace App\Listeners;

use App\Events\BadgeUnlocked;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Class UnlockBadgeListener
 * 
 */
class UnlockBadgeListener
{
    protected $badgeCheckers;

    /**
     * Handle the event.
     *
     * @param BadgeUnlocked $event The BadgeUnlocked event instance.
     * @throws \Exception if the badge name does not have a corresponding handler.
     */
    public function handle(BadgeUnlocked $event): void
    {
        $badgeName = $event->badgeName;
        $user = $event->user;

        // Check if user exists in the database
        if (!$user || !User::find($user->id)) {
            throw new \Exception("User not found in the database");
        }

        $badges = BadgeService::getBadges();
        
        if (array_key_exists($badgeName, $badges)) {
            $this->checkBadgeUnlock($user, $badgeName, $badges[$badgeName]);
        } else {
            // Throw an exception if the badge name is not recognized
            throw new \Exception("Unknown badge: {$badgeName}");
        }
    }

    protected function checkBadgeUnlock(User $user, $badgeName, $achievementCount): void
    {
        if ($user->achievements()->count() >= $achievementCount) {
            $this->unlockBadge($user, $badgeName);
        }
    }

    protected function unlockBadge(User $user, $badgeName): void
    {
        // Update the user's badge field in the database
        $user->update(['badge' => $badgeName]);
    }
}
