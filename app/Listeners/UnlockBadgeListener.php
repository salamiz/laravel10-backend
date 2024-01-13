<?php

namespace App\Listeners;

use App\Events\BadgeUnlocked;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Class UnlockBadgeListener
 *
 * Listens for BadgeUnlocked events and processes them based on the specific badge type.
 * Uses a mapping of badge names to corresponding handler functions to determine the appropriate action for each badge.
 */
class UnlockBadgeListener
{
    protected $badgeCheckers;

    /**
     * Constructor
     *
     * Initializes the mapping between badge names and their handling logic.
     */
    public function __construct()
    {
        $this->badgeCheckers = [
            'Beginner' => function ($user) { $this->checkBadgeUnlock($user, 'Beginner', 0); },
            'Intermediate' => function ($user) { $this->checkBadgeUnlock($user, 'Intermediate', 4); },
            'Advanced' => function ($user) { $this->checkBadgeUnlock($user, 'Advanced', 8); },
            'Master' => function ($user) { $this->checkBadgeUnlock($user, 'Master', 10); },
        ];
    }

    /**
     * Handle the event.
     *
     * Retrieves the badge name from the event and checks if there is a corresponding handler
     * in the $badgeCheckers array. If the handler exists, it is executed. Otherwise, an exception is thrown.
     *
     * @param BadgeUnlocked $event The BadgeUnlocked event instance.
     * @throws \Exception if the badge name does not have a corresponding handler.
     */
    public function handle(BadgeUnlocked $event): void
    {
        $badgeName = $event->badgeName;
        $user = $event->user;

        if (isset($this->badgeCheckers[$badgeName])) {
            $checker = $this->badgeCheckers[$badgeName];
            $checker($user);
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
