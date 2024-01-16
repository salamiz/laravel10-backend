<?php

namespace Tests\Feature\Listeners;

use Exception;
use Tests\TestCase;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Comment;
use App\Events\AchievementUnlocked;
use App\Listeners\UnlockAchievementListener;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class UnlockAchievementListenerTest extends TestCase
{
    use RefreshDatabase;  // Ensures each test is run with a fresh database.

    /**
     * Simulates the action of a user watching a certain number of lessons.
     *
     * @param User $user The user who is watching the lessons.
     * @param int $count The number of lessons to simulate watching.
     */
    protected function simulateWatchingLessons(User $user, int $count)
    {
        $existingCount = $user->lessons()->count();
        $additionalLessonsNeeded = $count - $existingCount;

        if ($additionalLessonsNeeded > 0) {
            $newLessons = Lesson::factory()->count($additionalLessonsNeeded)->create();

            foreach ($newLessons as $lesson) {
                $user->lessons()->attach($lesson, ['watched' => true]);
            }
        }
    }

    /**
     * Simulates the action of a user writing a certain number of comments.
     *
     * @param User $user The user who is writing the comments.
     * @param int $count The number of comments to simulate writing.
     */
    protected function simulateWritingComments(User $user, int $count)
    {
        $existingCount = $user->comments()->count();
        $additionalCommentsNeeded = $count - $existingCount;

        if ($additionalCommentsNeeded > 0) {
            Comment::factory()->count($additionalCommentsNeeded)->create(['user_id' => $user->id]);
        }
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
        $achievements = AchievementService::getAchievements();
        return array_map(function($achievement) {
            // Special handling for 'First Lesson Watched'
            if ($achievement === 'First Lesson Watched') {
                return [$achievement, 1];
            }
            return [$achievement, self::extractNumber($achievement)];
        }, $achievements['Lessons Watched']);
    }

    /**
     * Data provider for comments written achievements.
     */
    public static function commentsWrittenAchievements(): array
    {
        $achievements = AchievementService::getAchievements();
        return array_map(function($achievement) {
            // Special handling for 'First Comment Written'
            if ($achievement === 'First Comment Written') {
                return [$achievement, 1];
            }
            return [$achievement, self::extractNumber($achievement)];
        }, $achievements['Comments Written']);
    }

    // Extracts a number from a string (i.e. in an achievement name)
    private static function extractNumber($string)
    {
        // Use regular expression to extract numbers from the string
        return intval(preg_replace('/[^0-9]/', '', $string));
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
    ** @test */
    public function achievements_are_stored_cumulatively_and_not_overwritten()
    {
        $user = User::factory()->create();
        $this->simulateWatchingLessons($user, 50); // Simulate watching 50 lessons
        $this->simulateWritingComments($user, 20); // Simulate writing 20 comments
    
        $listener = new UnlockAchievementListener();
    
        // Handling achievements for lessons watched
        foreach (self::lessonsWatchedAchievements() as $lessonsAchievement) {
            [$achievementName,] = $lessonsAchievement;
            $listener->handle(new AchievementUnlocked($achievementName, $user));
        }
    
        // Handling achievements for comments written
        foreach (self::commentsWrittenAchievements() as $commentsAchievement) {
            [$achievementName,] = $commentsAchievement;
            $listener->handle(new AchievementUnlocked($achievementName, $user));
        }
    
        $user->refresh(); // Refresh the user model to update achievements
        $achievements = $user->achievements->pluck('name')->toArray();
        // Asserting that all 10 achievements are present
        foreach (array_merge(self::lessonsWatchedAchievements(), self::commentsWrittenAchievements()) as $achievement) {
            [$achievementName,] = $achievement;
            $this->assertContains($achievementName, $achievements, "Failed to assert that the achievement '{$achievementName}' was unlocked.");
        }
    }



    /** Test to check User with no lesson watched does not unlock an achievement
     * 
     * @test
     * @dataProvider lessonsWatchedAchievements
     */
    public function user_with_no_lessons_watched_does_not_unlock_achievements($achievementName, $threshold)
    {
        $user = User::factory()->create();
        $listener = new UnlockAchievementListener();

        // Simulating that the user has not watched any lessons
        // No need to attach lessons to the user

        // Handle the achievement unlocked event for each threshold
        $listener->handle(new AchievementUnlocked($achievementName, $user));

        // Assert that the achievement was not unlocked
        $this->assertAchievementNotUnlocked($user, $achievementName);
    }

    /** Test to check User with no comments written does not unlock an achievement
     * 
     * @test
     * @dataProvider commentsWrittenAchievements
     */
    public function user_with_no_comments_written_does_not_unlock_achievements($achievementName, $threshold)
    {
        $user = User::factory()->create();
        $listener = new UnlockAchievementListener();

        // Simulating that the user has not written any comments
        // No need to create comments for the user

        // Handle the achievement unlocked event for each threshold
        $listener->handle(new AchievementUnlocked($achievementName, $user));

        // Assert that the achievement was not unlocked
        $this->assertAchievementNotUnlocked($user, $achievementName);
    }



    /** Test for invalid user on lessonwatched
     * 
    * @test
    * @dataProvider lessonsWatchedAchievements
    */
    public function it_handles_invalid_user_data_for_lessons_watched($achievementName, $threshold)
    {
        // Expect an exception if the class is designed to throw one on invalid data
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found in the database');

        $listener = new UnlockAchievementListener();

        // Create a user instance without persisting it to the database, leading to an incomplete user object
        $invalidUser = new User();

        // Handle the achievement unlocked event with invalid user data
        $listener->handle(new AchievementUnlocked($achievementName, $invalidUser));
    }

    /** Test for invalid user on commentwritte
     * 
    * @test
    * @dataProvider commentsWrittenAchievements
    */
    public function it_handles_invalid_user_data_for_comments_written($achievementName, $threshold)
    {
        // Expect an exception if the class is designed to throw one on invalid data
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found in the database');

        $listener = new UnlockAchievementListener();

        // Create a user instance without persisting it to the database, leading to an incomplete user object
        $invalidUser = new User();

        // Handle the achievement unlocked event with invalid user data
        $listener->handle(new AchievementUnlocked($achievementName, $invalidUser));
    }



    /**
     * @test
     * @dataProvider lessonsWatchedAchievements
     */
    public function it_correctly_unlocks_and_stores_lessons_watched_achievements_in_database($achievementName, $threshold)
    {
        $user = User::factory()->create();
        $this->simulateWatchingLessons($user, $threshold);
        $listener = new UnlockAchievementListener();

        // Handle the achievement unlocked event
        $listener->handle(new AchievementUnlocked($achievementName, $user));

        // Assert that the achievement is stored in the database
        $this->assertDatabaseHas('achievements', [
            'name' => $achievementName
        ]);
    }

    /**
     * @test
     * @dataProvider commentsWrittenAchievements
     */
    public function it_correctly_unlocks_and_stores_comments_written_achievements_in_database($achievementName, $threshold)
    {
        $user = User::factory()->create();
        $this->simulateWritingComments($user, $threshold);
        $listener = new UnlockAchievementListener();

        // Handle the achievement unlocked event
        $listener->handle(new AchievementUnlocked($achievementName, $user));

        // Assert that the achievement is stored in the database
        $this->assertDatabaseHas('achievements', [
            'name' => $achievementName
        ]);
    }


    /**
    * @test
    * @dataProvider lessonsWatchedAchievements
    */
    public function it_handles_concurrency_without_creating_duplicate_achievements($achievementName, $threshold)
    {
        $user = User::factory()->create();
        $this->simulateWatchingLessons($user, $threshold);

        $listener = new UnlockAchievementListener();

        // Simulate concurrent processing by dispatching multiple events in parallel
        $concurrentRequests = 5;
        $jobs = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $jobs[] = function () use ($listener, $user, $achievementName) {
                $listener->handle(new AchievementUnlocked($achievementName, $user));
            };
        }

        // Run the jobs in parallel. This is a conceptual implementation as PHP does not support multi-threading natively
        foreach ($jobs as $job) {
            // This is where I would normally dispatch the job asynchronously
            $job();
        }

        // Assert that only one instance of the achievement is unlocked
        $achievementsCount = $user->achievements()->where('name', $achievementName)->count();
        $this->assertEquals(1, $achievementsCount, "Duplicate achievements were created for '{$achievementName}'");
    }


    /**

     * Tests that an exception is thrown for an invalid achievement name.
     *
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
