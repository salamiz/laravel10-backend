<?php

// Define a namespace for the Listener class
namespace App\Listeners;

// Import necessary classes
use App\Events\AchievementUnlocked;
use App\Models\User;
use App\Services\AchievementService;

// Listener class for unlocking achievements
class UnlockAchievementListener
{
    // Property to hold achievement checkers
    protected $achievementCheckers;

    // Constructor to initialize achievement checkers
    public function __construct()
    {
        $this->initializeAchievementCheckers();
    }

    // Initializes achievement checkers by loading achievements from a service
    private function initializeAchievementCheckers()
    {
        // Retrieve achievements from the AchievementService
        $achievements = AchievementService::getAchievements();
        $this->achievementCheckers = [];

        // Loop through each achievement category and name
        foreach ($achievements as $category => $achievementNames) {
            foreach ($achievementNames as $achievementName) {
                // Handle 'First Lesson Watched' and 'First Comment Written' specifically
                if ($achievementName === 'First Lesson Watched') {
                    $this->achievementCheckers[$achievementName] = function ($user) {
                        $this->checkFirstLessonWatched($user);
                    };
                } elseif ($achievementName === 'First Comment Written') {
                    $this->achievementCheckers[$achievementName] = function ($user) {
                        $this->checkFirstCommentWritten($user);
                    };
                } else {
                    // Create a checker function for other achievements
                    $this->achievementCheckers[$achievementName] = function ($user) use ($achievementName, $category) {
                        $this->checkAchievement($user, $achievementName, $category);
                    };
                }
            }
        }
    }

    // Handle method to process AchievementUnlocked event
    public function handle(AchievementUnlocked $event): void
    {
        // Extract achievement name and user from the event
        $achievementName = $event->achievementName;
        $user = $event->user;

        // Throw an exception if the user does not exist in the database
        if (!$user || !User::find($user->id)) {
            throw new \Exception("User not found in the database");
        }

        // Check if the achievement checker exists for the given achievement name
        if (isset($this->achievementCheckers[$achievementName])) {
            $checker = $this->achievementCheckers[$achievementName];
            $checker($user);
        } else {
            // Throw an exception if the achievement name is not recognized
            throw new \Exception("Unknown achievement: {$achievementName}");
        }
    }

    // Check and process the specific achievement for the user
    protected function checkAchievement(User $user, $achievementName, $category): void
    {
        // Handle different categories of achievements
        if ($category === 'Lessons Watched') {
            $this->checkLessonsWatched($user, $achievementName);
        } elseif ($category === 'Comments Written') {
            $this->checkCommentsWritten($user, $achievementName);
        }
        // Extend with more categories if necessary
    }

    // Check if the user has watched a specific number of lessons
    protected function checkLessonsWatched(User $user, $achievementName): void
    {
        // Extract the number from the achievement name
        $number = $this->extractNumber($achievementName);
        // Skip if no valid number is extracted
        if ($number === null) {
            return;
        }
        // Handle first lesson watched separately
        if ($number === 1) {
            $this->checkFirstLessonWatched($user);
        } else {
            // Handle generic cases for lessons watched
            $this->checkGenericLessonsWatched($user, $number);
        }
    }

    // Check if the user has written a specific number of comments
    protected function checkCommentsWritten(User $user, $achievementName): void
    {
        // Extract the number from the achievement name
        $number = $this->extractNumber($achievementName);
        // Skip if no valid number is extracted
        if ($number === null) {
            return;
        }
        // Handle first comment written separately
        if ($number === 1) {
            $this->checkFirstCommentWritten($user);
        } else {
            // Handle generic cases for comments written
            $this->checkGenericCommentsWritten($user, $number);
        }
    }

    // Check if the user has watched their first lesson
    protected function checkFirstLessonWatched(User $user): void
    {
        // Check if the user has watched any lesson
        if ($user->lessons()->wherePivot('watched', true)->exists()) {
            // Unlock the 'First Lesson Watched' achievement
            $this->unlockAchievement($user, 'First Lesson Watched');
        }
    }

    // Check if the user has watched a certain number of lessons
    protected function checkGenericLessonsWatched(User $user, $threshold): void
    {
        // Ensure the first lesson watched achievement is unlocked
        $this->checkFirstLessonWatched($user);
        // Count the number of lessons watched by the user
        $watchedCount = $user->watched()->count();
        // Unlock the achievement if the threshold is met
        if ($watchedCount >= $threshold) {
            $this->unlockAchievement($user, "{$threshold} Lessons Watched");
        }
    }

    // Check if the user has written their first comment
    protected function checkFirstCommentWritten(User $user): void
    {
        // Check if the user has written any comments
        if ($user->comments()->exists()) {
            // Unlock the 'First Comment Written' achievement
            $this->unlockAchievement($user, 'First Comment Written');
        }
    }

    // Check if the user has written a certain number of comments
    protected function checkGenericCommentsWritten(User $user, $threshold): void
    {
        // Ensure the first comment written achievement is unlocked
        $this->checkFirstCommentWritten($user);
        // Count the number of comments written by the user
        $writtenCount = $user->comments()->count();
        // Unlock the achievement if the threshold is met
        if ($writtenCount >= $threshold) {
            $this->unlockAchievement($user, "{$threshold} Comments Written");
        }
    }

    // Unlock a specific achievement for the user
    protected function unlockAchievement(User $user, $achievementName): void
    {
        // Check if the achievement already exists for the user
        $achievementExists = $user->achievements()->where('name', $achievementName)->exists();
        // Create the achievement record if it does not exist
        if (!$achievementExists) {
            $user->achievements()->create(['name' => $achievementName]);
        }
    }

    // Extracts a number from a string (i.e. in an achievement name)
    private function extractNumber($string)
    {
        if (preg_match('/\d+/', $string, $matches)) {
            return intval($matches[0]);
        }
        return null;
    }
}

