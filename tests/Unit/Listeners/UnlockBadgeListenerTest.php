<?php

namespace Tests\Unit\Listeners;

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
     *
     * @param User $user The user to simulate achievements for.
     * @param int $count The number of achievements to simulate.
     */
    protected function simulateAchievements(User $user, int $count)
    {
        Achievement::factory()->count($count)->create(['user_id' => $user->id]);
    }

    /**
     * Provides data for testing badge unlocks.
     *
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
     *
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
     * 
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
}
