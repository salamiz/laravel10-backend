<?php

namespace Tests\Unit\Listeners;

use Exception;
use Tests\TestCase;
use App\Models\User;
use App\Models\Achievement;
use App\Events\BadgeUnlocked;
use App\Listeners\UnlockBadgeListener;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnlockBadgeListenerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simulates unlocking achievements for a user.
     * @param User $user The user to simulate achievements for.
     * @param int $count The number of achievements to simulate.
     */
    protected function simulateAchievements(User $user, int $count)
    {
        Achievement::factory()->count($count)->create(['user_id' => $user->id]);
    }

    /**
     * Provides data for testing badge unlocks.
     * @return array
     */
    public static function badgeDataProvider()
    {
        return [
            'Beginner badge' => [0, 'Beginner'],
            'Intermediate badge' => [4, 'Intermediate'],
            'Advanced badge' => [8, 'Advanced'],
            'Master badge' => [10, 'Master'],
        ];
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
     * Tests that an exception is thrown for an invalid badge name.
     *
     * This method validates that the UnlockBadgeListener throws an exception when
     * it encounters a BadgeUnlocked event with a badge name that is not recognized.
     */
    /** @test */
    public function it_throws_exception_for_unknown_badge_name()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown badge: Invalid Badge');

        $user = User::factory()->create();
        $listener = new UnlockBadgeListener();

        // Dispatch the event with an invalid badge name
        $listener->handle(new BadgeUnlocked('Invalid Badge', $user));
    }
}
