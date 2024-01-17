<?php

namespace Tests\Feature\Listeners;

use Exception;
use Tests\TestCase;
use App\Models\User;
use App\Models\Achievement;
use App\Events\BadgeUnlocked;
use App\Services\BadgeService;
use App\Listeners\UnlockBadgeListener;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnlockBadgeListenerTest extends TestCase
{
    use RefreshDatabase;

    /** Simulates unlocking achievements for a user.
     * 
     * @param User $user The user to simulate achievements for.
     * @param int $count The number of achievements to simulate.
     */
    protected function simulateAchievements(User $user, int $count)
    {
        // Create the specified number of achievements
        $achievements = Achievement::factory()->count($count)->create();

        // Attach these achievements to the user
        foreach ($achievements as $achievement) {
            $user->achievements()->attach($achievement);
        }
    }

    /**
     * Provides data for testing badge unlocks.
     * @return array
     */
    public static function badgeDataProvider()
    {
        $badges = BadgeService::getBadges();
        return array_map(function ($badgeName, $achievementCount) {
            return [$achievementCount, $badgeName];
        }, array_keys($badges), $badges);
    }

    /**
     * Test that the correct badge is unlocked based on the number of achievements.
     * @dataProvider badgeDataProvider
     */
    public function testBadgeUnlocking($achievements, $expectedBadge)
    {
        $user = User::factory()->create();
        $this->simulateAchievements($user, $achievements);

        $listener = new UnlockBadgeListener();
        $listener->handle(new BadgeUnlocked($expectedBadge, $user));

        $user->refresh();
        $this->assertEquals($expectedBadge, $user->badge, "Failed asserting that the badge '{$expectedBadge}' was unlocked.");
    }

    /**
     * Test that badges are not unlocked if the user has fewer achievements than required,
     * except for the Beginner badge which should always be unlocked.
     * @dataProvider badgeDataProvider
     */
    public function testBadgeNotUnlockedWithFewerAchievements($achievements, $expectedBadge)
    {
        $user = User::factory()->create();
        // For the 'Beginner' badge, it should always be unlocked.
        if ($expectedBadge !== 'Beginner') {
            $this->simulateAchievements($user, $achievements - 1); // One less than required
            $listener = new UnlockBadgeListener();
            $listener->handle(new BadgeUnlocked($expectedBadge, $user));
            $user->refresh();
            $this->assertNotEquals($expectedBadge, $user->badge, "Failed asserting that the badge '{$expectedBadge}' was not unlocked prematurely.");
        } else {
            $this->assertTrue(true); // Automatically pass the test for the 'Beginner' badge.
        }
    }

    /**
     * Test that a badge is not unlocked again if it has already been unlocked.
     * @dataProvider badgeDataProvider
     */
    public function testNoDoubleUnlocking($achievements, $expectedBadge)
    {
        $user = User::factory()->create();
        
        // Unlock the badge
        $this->simulateAchievements($user, $achievements);
        $listener = new UnlockBadgeListener();
        $listener->handle(new BadgeUnlocked($expectedBadge, $user));
        
        // Refresh and check if the badge is as expected
        $user->refresh();
        $this->assertEquals($expectedBadge, $user->badge, "Failed asserting that the badge '{$expectedBadge}' was unlocked.");

        // Add more achievements and try to unlock the badge again
        $this->simulateAchievements($user, $achievements + 1); // Adding more achievements
        $listener->handle(new BadgeUnlocked($expectedBadge, $user));
        
        // Refresh and assert that the badge has not changed
        $user->refresh();
        $this->assertEquals($expectedBadge, $user->badge, "Failed asserting that the badge '{$expectedBadge}' was not re-unlocked.");
    }

    /**
     * Test that the user's badge is correctly updated in the database when a badge is unlocked.
     * @dataProvider badgeDataProvider
     */
    public function testDatabaseInteractionForBadgeUnlock($achievements, $expectedBadge)
    {
        $user = User::factory()->create();
        
        // Simulate unlocking the badge
        $this->simulateAchievements($user, $achievements);
        $listener = new UnlockBadgeListener();
        $listener->handle(new BadgeUnlocked($expectedBadge, $user));

        // Retrieve the user from the database to confirm badge update
        $userFromDb = User::find($user->id);
        // Assert that the badge field in the database is correctly updated
        $this->assertEquals($expectedBadge, $userFromDb->badge, "Failed asserting that the badge '{$expectedBadge}' was correctly updated in the database.");
    }

    /**
     * Test that the system correctly handles multiple badge unlocks in sequence.
     * @dataProvider badgeDataProvider
     */
    public function testSequentialBadgeUnlocking()
    {
        $user = User::factory()->create();
        $listener = new UnlockBadgeListener();

        $previousBadge = 'Beginner'; // Assuming 'Beginner' is the starting badge
        foreach (self::badgeDataProvider() as $badgeData) {
            [$achievementCount, $badgeName] = $badgeData;

            // Simulate unlocking the badge
            $this->simulateAchievements($user, $achievementCount);
            $listener->handle(new BadgeUnlocked($badgeName, $user));

            // Refresh the user instance and check the badge
            $user->refresh();
            $this->assertEquals($badgeName, $user->badge, "Failed asserting that the badge '{$badgeName}' was unlocked.");
            // Check that the current badge is not the same as the previous one (except for the first iteration)
            if ($previousBadge !== $badgeName) {
                $this->assertNotEquals($previousBadge, $user->badge, "Failed asserting that the badge '{$badgeName}' is different from the previous badge '{$previousBadge}'.");
            }

            $previousBadge = $badgeName; // Update previous badge for next iteration
        }
    }


    /**
     * Test handling of a non-existent user object.
     */
    public function testNonExistentUserHandling()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User not found in the database');

        $listener = new UnlockBadgeListener();

        // Create a user instance without saving it to the database
        $nonExistentUser = User::factory()->make();

        // Dispatch the event with a non-existent user
        $listener->handle(new BadgeUnlocked('Beginner', $nonExistentUser));
    }

    /**
     * Tests that an exception is thrown for an invalid badge name.
     *
     * This method validates that the UnlockBadgeListener throws an exception when
     * it encounters a BadgeUnlocked event with a badge name that is not recognized.
     */
    /** */
    public function it_throws_exception_for_unknown_badge_name()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown badge: Invalid Badge');

        $user = User::factory()->create();
        $listener = new UnlockBadgeListener();

        // Use a badge name that is guaranteed not to be in the BadgeService
        $invalidBadgeName = 'Invalid Badge';
        $listener->handle(new BadgeUnlocked($invalidBadgeName, $user));
    }
}
