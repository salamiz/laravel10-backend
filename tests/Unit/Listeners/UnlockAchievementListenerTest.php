<?php

namespace Tests\Unit\Listeners;

use Exception;
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
     * Asserts that a specific achievement is unlocked a certain number of times.
     *
     * @param User $user The user to check for the achievement.
     * @param string $achievementName The name of the achievement to check.
     * @param int $expectedCount The expected count of times the achievement should be unlocked.
     */
    protected function assertAchievementUnlocked(User $user, string $achievementName, int $expectedCount = 1)
    {
        $actualCount = $user->achievements()->where('name', $achievementName)->count();
        $this->assertEquals(
            $expectedCount,
            $actualCount,
            "Failed to assert that the achievement '{$achievementName}' was unlocked exactly {$expectedCount} time(s)."
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
     * Data provider for lessons watched achievements.
     */
    public static function lessonsWatchedAchievements(): array
    {
        return [
            ['First Lesson Watched', 1],
            ['5 Lessons Watched', 5],
            ['10 Lessons Watched', 10],
            ['25 Lessons Watched', 25],
            ['50 Lessons Watched', 50],
        ];
    }

    /**
     * Data provider for comments written achievements.
     */
    public static function commentsWrittenAchievements(): array
    {
        return [
            ['First Comment Written', 1],
            ['3 Comments Written', 3],
            ['5 Comments Written', 5],
            ['10 Comments Written', 10],
            ['20 Comments Written', 20],
        ];
    }

    /**
     * @test
     * @dataProvider lessonsWatchedAchievements
     */
    public function it_unlocks_lessons_watched_achievements_correctly($achievement, $threshold)
    {
        $user = User::factory()->create();
        $listener = new UnlockAchievementListener();

        // Test just below the threshold
        $this->simulateWatchingLessons($user, $threshold - 1);
        $listener->handle(new AchievementUnlocked($achievement, $user));
        $this->assertAchievementNotUnlocked($user, $achievement);

        // Test at the threshold
        $this->simulateWatchingLessons($user, $threshold); // This makes total lessons equal to the threshold
        $listener->handle(new AchievementUnlocked($achievement, $user));
        $this->assertAchievementUnlocked($user, $achievement);

        // Test just above the threshold
        $this->simulateWatchingLessons($user, $threshold + 1); // This makes total lessons one more than the threshold
        $listener->handle(new AchievementUnlocked($achievement, $user));
        $this->assertAchievementUnlocked($user, $achievement, 1); // Ensure it's still unlocked only once
    }

    /**
     * @test
     * @dataProvider commentsWrittenAchievements
     */
    public function it_unlocks_comments_written_achievements_correctly($achievement, $threshold)
    {
        $user = User::factory()->create();
        $listener = new UnlockAchievementListener();

        // Test just below the threshold
        $this->simulateWritingComments($user, $threshold - 1);
        $listener->handle(new AchievementUnlocked($achievement, $user));
        $this->assertAchievementNotUnlocked($user, $achievement); // Just below the threshold: Should not unlock the achievement.

        // Test at the threshold
        $this->simulateWritingComments($user, $threshold); // This makes total comments equal to the threshold
        $listener->handle(new AchievementUnlocked($achievement, $user));
        $this->assertAchievementUnlocked($user, $achievement);

        // Test just above the threshold
        $this->simulateWritingComments($user, $threshold + 1); // This makes total comments one more than the threshold
        $listener->handle(new AchievementUnlocked($achievement, $user));
        $this->assertAchievementUnlocked($user, $achievement, 1); // Ensure it's still unlocked only once
    }

    /**
     * Tests that achievements are cumulatively stored without being overwritten.
     *
     * This test checks if the UnlockAchievementListener correctly accumulates
     * achievements for a user without overwriting existing ones. 
     */
    /** @test */
    public function achievements_are_stored_cumulatively_and_not_overwritten()
    {
        $user = User::factory()->create();
        $this->simulateWatchingLessons($user, 50);
        $this->simulateWritingComments($user, 20);

        $listener = new UnlockAchievementListener();
        $listener->handle(new AchievementUnlocked('50 Lessons Watched', $user));
        $listener->handle(new AchievementUnlocked('20 Comments Written', $user));

        $user->refresh();
        $achievements = $user->achievements->pluck('name')->toArray();

        // Define expected achievements
        $expectedAchievements = [
            'First Lesson Watched', '5 Lessons Watched', '10 Lessons Watched', 
            '25 Lessons Watched', '50 Lessons Watched', 'First Comment Written', 
            '3 Comments Written', '5 Comments Written', '10 Comments Written', 
            '20 Comments Written'
        ];
        foreach ($expectedAchievements as $achievement) {
            $this->assertContains($achievement, $achievements, "Failed to assert that the achievement '{$achievement}' was unlocked.");
        }
    }

    /**
     * Tests that an exception is thrown for an invalid achievement name.
     *
     * This test ensures that the UnlockAchievementListener throws an exception when
     * handling an AchievementUnlocked event with an unrecognized achievement name. 
     */
    /** @test */
    public function it_throws_exception_for_unknown_achievement_name()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown achievement: Invalid Achievement');

        $user = User::factory()->create();
        $listener = new UnlockAchievementListener();

        // Dispatch the event with an invalid achievement name
        $listener->handle(new AchievementUnlocked('Invalid Achievement', $user));
    }

}
