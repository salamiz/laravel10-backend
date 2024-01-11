<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UnlockAchievementListener
{

    /**
     * Handle the event.
     */
    public function handle(AchievementUnlocked $event): void
    {
        $achievementName = $event->achievementName;
        $user = $event->user;

        switch ($achievementName) {
            case 'First Lesson Watched':
                $this->checkFirstLessonWatched($user);
                break;
            case '5 Lessons Watched':
                $this->checkLessonsWatched($user, 5);
                break;
            case '10 Lessons Watched':
                $this->checkLessonsWatched($user, 10);
                break;
            case '25 Lessons Watched':
                $this->checkLessonsWatched($user, 25);
                break;
            case '50 Lessons Watched':
                $this->checkLessonsWatched($user, 50);
                break;
            case 'First Comment Written':
                $this->checkFirstCommentWritten($user);
                break;
            case '3 Comments Written':
                $this->checkCommentsWritten($user, 3);
                break;
            case '5 Comments Written':
                $this->checkCommentsWritten($user, 5);
                break;
            case '10 Comments Written':
                $this->checkCommentsWritten($user, 10);
                break;
            case '20 Comments Written':
                $this->checkCommentsWritten($user, 20);
                break;
            default:
                // Handle other achievements or throw an exception
                break;
        }
    }

    protected function checkFirstLessonWatched(User $user): void
    {
        if (!$user->watched()->exists()) {
            $this->unlockAchievement($user, 'First Lesson Watched');
        }
    }

    protected function checkLessonsWatched(User $user, $lessonCount): void
    {
        if ($user->watched()->count() >= $lessonCount) {
            $this->unlockAchievement($user, "{$lessonCount} Lessons Watched");
        }
    }

    protected function checkFirstCommentWritten(User $user): void
    {
        if (!$user->comments()->exists()) {
            $this->unlockAchievement($user, 'First Comment Written');
        }
    }

    protected function checkCommentsWritten(User $user, $commentCount): void
    {
        if ($user->comments()->count() >= $commentCount) {
            $this->unlockAchievement($user, "{$commentCount} Comments Written");
        }
    }
    
    protected function unlockAchievement(User $user, $achievementName): void
    {
        // Find or create the achievement record for the user
        $achievement = $user->achievements()->where('name', $achievementName)->first();

        if (!$achievement) {
            // If the user doesn't have the achievement, create it
            $user->achievements()->create(['name' => $achievementName]);
        }
    }

}
