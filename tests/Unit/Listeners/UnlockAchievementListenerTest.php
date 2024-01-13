<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Comment;
use App\Events\AchievementUnlocked;
use App\Listeners\UnlockAchievementListener;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for the UnlockAchievementListener class.
 *
 * This test class ensures that the UnlockAchievementListener correctly handles the unlocking of achievements
 * based on various user actions like watching lessons and writing comments.
 */
class UnlockAchievementListenerTest extends TestCase
{
    use RefreshDatabase;  // Ensures each test is run with a fresh database.

    /**
     * Simulates the action of a user watching a certain number of lessons.
     *
     * This method creates a specified number of lessons and attaches them to the user's watched list.
     *
     * @param User $user The user who is watching the lessons.
     * @param int $count The number of lessons to simulate watching.
     */
    protected function simulateWatchingLessons(User $user, int $count)
    {
        $lessons = Lesson::factory()->count($count)->create();

        foreach ($lessons as $lesson) {
            // Attach each lesson to the user with 'watched' status set to true
            $user->lessons()->attach($lesson, ['watched' => true]);
        }
    }

    /**
     * Simulates the action of a user writing a certain number of comments.
     *
     * This method creates a specified number of comments and associates them with the user.
     *
     * @param User $user The user who is writing the comments.
     * @param int $count The number of comments to simulate writing.
     */
    protected function simulateWritingComments(User $user, int $count)
    {
        Comment::factory()->count($count)->create(['user_id' => $user->id]);
    }

    /**
     * Asserts that a specific achievement was unlocked for the user.
     *
     * @param User $user The user to check for the achievement.
     * @param string $achievementName The name of the achievement to check.
     */
    protected function assertAchievementUnlocked(User $user, string $achievementName)
    {
        $this->assertTrue(
            $user->achievements()->where('name', $achievementName)->exists(),
            "Failed to assert that the achievement '{$achievementName}' was unlocked."
        );
    }

    /**
     * Asserts that a specific achievement was not unlocked for the user.
     *
     * @param User $user The user to check for the achievement.
     * @param string $achievementName The name of the achievement to check.
     */
    protected function assertAchievementNotUnlocked(User $user, string $achievementName)
    {
        $this->assertFalse(
            $user->achievements()->where('name', $achievementName)->exists(),
            "Failed to assert that the achievement '{$achievementName}' was not unlocked."
        );
    }

    /**
     * Tests the unlocking of various achievements.
     *
     * This test iterates over a set of predefined achievements, simulating the conditions for each one
     * and verifying that the UnlockAchievementListener properly unlocks the achievement.
     */

    /** @test */
    public function it_correctly_handles_all_achievements()
    {
        // Defines the achievements to test, including the method to simulate them and the count required.
        $achievements = [
            // Lessons Watched Achievements
            'First Lesson Watched' => ['method' => 'simulateWatchingLessons', 'count' => 1],
            '5 Lessons Watched' => ['method' => 'simulateWatchingLessons', 'count' => 5],
            '10 Lessons Watched' => ['method' => 'simulateWatchingLessons', 'count' => 10],
            '25 Lessons Watched' => ['method' => 'simulateWatchingLessons', 'count' => 25],
            '50 Lessons Watched' => ['method' => 'simulateWatchingLessons', 'count' => 50],

            // Comments Written Achievements
            'First Comment Written' => ['method' => 'simulateWritingComments', 'count' => 1],
            '3 Comments Written' => ['method' => 'simulateWritingComments', 'count' => 3],
            '5 Comments Written' => ['method' => 'simulateWritingComments', 'count' => 5],
            '10 Comments Written' => ['method' => 'simulateWritingComments', 'count' => 10],
            '20 Comments Written' => ['method' => 'simulateWritingComments', 'count' => 20]
        ];

        foreach ($achievements as $name => $data) {
            $user = User::factory()->create();
            // Calls the simulation method based on the achievement definition.
            $this->{$data['method']}($user, $data['count']);

            $listener = new UnlockAchievementListener();
            // Dispatches the AchievementUnlocked event and handles it with the listener.
            $listener->handle(new AchievementUnlocked($name, $user));

            $user->refresh();// Refreshes the user model to get updated data.
            // Asserts that the achievement was correctly unlocked.
            $this->assertAchievementUnlocked($user, $name);
        }
    }


    /**
     * Tests that achievements are not unlocked under non-qualifying conditions.
     *
     * This test iterates over a set of predefined achievements and simulates conditions
     * that are just shy of what's required to unlock each achievement. It then asserts
     * that these achievements are not unlocked under these non-qualifying conditions.
     */
    /** @test */
    public function it_does_not_unlock_achievements_for_non_qualifying_conditions()
    {
        // Define each achievement along with its non-qualifying condition.
        $nonQualifyingAchievements = [
            // For lessons watched and Comments Written achievements, simulate one less than required
            'First Lesson Watched' => ['method' => 'simulateWatchingLessons', 'count' => 0],
            '5 Lessons Watched' => ['method' => 'simulateWatchingLessons', 'count' => 4],
            '10 Lessons Watched' => ['method' => 'simulateWatchingLessons', 'count' => 9],
            '25 Lessons Watched' => ['method' => 'simulateWatchingLessons', 'count' => 24],
            '50 Lessons Watched' => ['method' => 'simulateWatchingLessons', 'count' => 49],
            'First Comment Written' => ['method' => 'simulateWritingComments', 'count' => 0],
            '3 Comments Written' => ['method' => 'simulateWritingComments', 'count' => 2],
            '5 Comments Written' => ['method' => 'simulateWritingComments', 'count' => 4],
            '10 Comments Written' => ['method' => 'simulateWritingComments', 'count' => 9],
            '20 Comments Written' => ['method' => 'simulateWritingComments', 'count' => 19]
        ];

        // Iterate over each non-qualifying achievement condition.
        foreach ($nonQualifyingAchievements as $name => $data) {
            $user = User::factory()->create(); // Create a new user for each test iteration.

            // Simulate the non-qualifying condition.
            $this->{$data['method']}($user, $data['count']);

            $listener = new UnlockAchievementListener();
            // Dispatch and handle the AchievementUnlocked event.
            $listener->handle(new AchievementUnlocked($name, $user));

            $user->refresh(); // Refresh the user to get the latest data from the database.

            // Assert that the achievement was not unlocked.
            $this->assertAchievementNotUnlocked($user, $name);
        }
    }

}
