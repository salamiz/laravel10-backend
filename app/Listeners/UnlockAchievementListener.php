<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Class UnlockAchievementListener
 *
 * This class listens for AchievementUnlocked events and processes them based on the specific achievement type.
 * It uses a mapping of achievement names to corresponding handler functions to determine the appropriate action for each achievement.
 */
class UnlockAchievementListener
{
    protected $achievementCheckers;

    /**
     * Constructor
     *
     * Initializes the mapping between achievement names and their handling logic.
     * Each entry in the $achievementCheckers array corresponds to a specific achievement type and a closure that encapsulates its handling logic.
     */
    public function __construct()
    {
        $this->achievementCheckers = [
            'First Lesson Watched' => function ($user) { $this->checkFirstLessonWatched($user); },
            '5 Lessons Watched' => function ($user) { $this->checkLessonsWatched($user, 5); },
            '10 Lessons Watched' => function ($user) { $this->checkLessonsWatched($user, 10); },
            '25 Lessons Watched' => function ($user) { $this->checkLessonsWatched($user, 25); },
            '50 Lessons Watched' => function ($user) { $this->checkLessonsWatched($user, 50); },
            'First Comment Written' => function ($user) { $this->checkFirstCommentWritten($user); },
            '3 Comments Written' => function ($user) { $this->checkCommentsWritten($user, 3); },
            '5 Comments Written' => function ($user) { $this->checkCommentsWritten($user, 5); },
            '10 Comments Written' => function ($user) { $this->checkCommentsWritten($user, 10); },
            '20 Comments Written' => function ($user) { $this->checkCommentsWritten($user, 20); },
        ];
    }

    /**
     * Handle the event.
     *
     * This method is triggered when an AchievementUnlocked event is dispatched.
     * It retrieves the achievement name from the event and checks if there is a corresponding handler in the $achievementCheckers array.
     * If the handler exists, it is executed. Otherwise, an exception is thrown.
     *
     * @param AchievementUnlocked $event The AchievementUnlocked event instance.
     * @throws \Exception if the achievement name does not have a corresponding handler.
     */
    public function handle(AchievementUnlocked $event): void
    {
        $achievementName = $event->achievementName;
        $user = $event->user;

        // Check if user exists in the database
        if (!$user || !User::find($user->id)) {
            throw new \Exception("User not found in the database");
        }
        
        if (isset($this->achievementCheckers[$achievementName])) {
            $checker = $this->achievementCheckers[$achievementName];
            $checker($user);
        } else {
            // Throw an exception if the achievement name is not recognized
            throw new \Exception("Unknown achievement: {$achievementName}");
        }
    }

    // Return thresholds for 'Lessons Watched' achievements
    protected function getLessonWatchedThresholds(): array
    {
        return $this->extractThresholds('Lessons Watched');
    }
    
    // Return thresholds for 'Comments Written' achievements
    protected function getCommentWrittenThresholds(): array
    {
        return $this->extractThresholds('Comments Written');
    }
    
    // Extract numeric thresholds from the keys of the $achievementCheckers array for a specific type of achievement
    protected function extractThresholds($type): array
    {
        return array_map(function ($key) {
            // Remove all non-numeric characters from the key and convert it to an integer
            // This extracts the numeric threshold (e.g., '5' from '5 Lessons Watched')
            return intval(preg_replace('/[^0-9]/', '', $key));
        }, array_filter(array_keys($this->achievementCheckers), function ($key) use ($type) {
            // Filter the keys of the $achievementCheckers array
            // Include only those keys that contain the specified type (e.g., 'Lessons Watched')
            return strpos($key, $type) !== false;
        }));
    }
    

    /**
     * Checks if the user has watched their first lesson and unlocks the achievement if so.
     * 
     * This function queries the lessons associated with the user, specifically looking 
     * for any lessons where the 'watched' pivot field is set to true. If at least one 
     * watched lesson is found, it means the user has watched their first lesson, 
     * and the corresponding achievement is unlocked.
     * 
     * @param User $user The user for whom to check the achievement.
     */
    protected function checkFirstLessonWatched(User $user): void
    {
        if ($user->lessons()->wherePivot('watched', true)->exists()) {
            $this->unlockAchievement($user, 'First Lesson Watched');
        }
    }

    /**
     * Check and unlock lesson watching achievements for a user.
     *
     * This method is responsible for unlocking achievements related to watching lessons.
     * It first checks if the 'First Lesson Watched' achievement needs to be unlocked.
     * Then, it iterates through predefined lesson thresholds and unlocks achievements
     * if the user has watched a number of lessons meeting or exceeding these thresholds.
     *
     * @param User $user The user whose achievements are being checked.
     * @param int $lessonCount The number of lessons recently watched.
     */
    protected function checkLessonsWatched(User $user, $lessonCount): void
    {
        $this->checkFirstLessonWatched($user); // Always check for the first lesson watched.
        $watchedCount = $user->watched()->count(); // Count the total number of lessons watched by the user so far.

        // Check for each threshold up to the current count, starting from 5 as 'First Lesson Watched' is already checked.
        foreach ($this->getLessonWatchedThresholds() as $threshold) {
            if ($watchedCount >= $threshold && $lessonCount >= $threshold) {
                $this->unlockAchievement($user, "{$threshold} Lessons Watched");
            }
        }
    }

    /**
     * Checks if the user has written their first comment and unlocks the achievement if so.
     * 
     * This function queries the comments made by the user. If at least one comment exists,
     * it indicates that the user has written their first comment, and the corresponding 
     * achievement is unlocked.
     * 
     * @param User $user The user for whom to check the achievement.
     */
    protected function checkFirstCommentWritten(User $user): void
    {
        if ($user->comments()->exists()) {
            $this->unlockAchievement($user, 'First Comment Written');
        }
    }

    /**
     * Check and unlock comment writing achievements for a user.
     *
     * This method is responsible for unlocking achievements related to writing comments.
     * It first checks if the 'First Comment Written' achievement needs to be unlocked.
     * Then, it iterates through predefined comment thresholds and unlocks achievements
     * if the user has written a number of comments meeting or exceeding these thresholds.
     *
     * @param User $user The user whose achievements are being checked.
     * @param int $commentCount The number of comments recently written.
     */
    protected function checkCommentsWritten(User $user, $commentCount): void
    {
        $this->checkFirstCommentWritten($user); // Always check for the first comment written.
        $writtenCount = $user->comments()->count(); // Count the total number of comments written by the user.

        // Check for each threshold up to the current count, starting from 3 as 'First Comment Written' is already checked.
        foreach ($this->getCommentWrittenThresholds() as $threshold) {
            if ($writtenCount >= $threshold && $commentCount >= $threshold) {
                $this->unlockAchievement($user, "{$threshold} Comments Written");
            }
        }
    }
    
    /**
     * Unlocks an achievement for the user, provided the achievement does not already exist.
     * 
     * This function first checks if the specified achievement already exists for the user.
     * If it doesn't exist, it creates a new achievement record with the given name for the user.
     * This ensures that achievements are not duplicated for a user.
     * 
     * @param User $user The user for whom to unlock the achievement.
     * @param string $achievementName The name of the achievement to unlock.
     */
    protected function unlockAchievement(User $user, $achievementName): void
    {
        // Ensure the achievement does not already exist
        $achievementExists = $user->achievements()->where('name', $achievementName)->exists();
        if (!$achievementExists) {
            $user->achievements()->create(['name' => $achievementName]);
        }
    }

}
