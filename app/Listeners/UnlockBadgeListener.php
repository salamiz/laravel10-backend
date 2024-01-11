<?php

namespace App\Listeners;

use App\Events\BadgeUnlocked;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UnlockBadgeListener
{
    /**
     * Handle the event.
     */
    public function handle(BadgeUnlocked $event): void
    {
        // Logic to handle unlocking a badge
        $badgeName = $event->badgeName;
        $user = $event->user;

        switch ($badgeName) {
            case 'Beginner':
                $this->checkBadgeUnlock($user, 'Beginner', 0);
                break;
            case 'Intermediate':
                $this->checkBadgeUnlock($user, 'Intermediate', 4);
                break;
            case 'Advanced':
                $this->checkBadgeUnlock($user, 'Advanced', 8);
                break;
            case 'Master':
                $this->checkBadgeUnlock($user, 'Master', 10);
                break;
            default:
                // Handle other badges or throw an exception
                break;
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
