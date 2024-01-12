<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Models\User;
use App\Events\BadgeUnlocked;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Class BadgeUnlockedTest
 *
 * This class performs unit tests for the BadgeUnlocked event.
 * It ensures that the event is dispatched with the correct badge name and user instance.
 *
 * Each test within this class validates that the BadgeUnlocked event carries
 * the expected data payload and behaves as intended under different scenarios.
 *
 * @package Tests\Unit\Events
 */
class BadgeUnlockedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the dispatching of the BadgeUnlocked event.
     *
     * This method confirms that the BadgeUnlocked event is dispatched correctly with a specific
     * badge name and user instance. It ensures that the event's payload matches the expected data.
     *
     * @return void
     * @test */
    public function testBadgeUnlockedEventDispatch()
    {
        // Fake event dispatching to isolate the test.
        Event::fake();
        
        // Arrange: Create a new user and specify a badge name.
        $user = User::factory()->create();
        $badgeName = 'Beginner';

        // Act: Dispatch the BadgeUnlocked event.
        BadgeUnlocked::dispatch($badgeName, $user);

        // Assert: The event should be dispatched with the correct badge name and user.
        Event::assertDispatched(BadgeUnlocked::class, function ($event) use ($badgeName, $user) {
            return $event->badgeName === $badgeName && $event->user->id === $user->id;
        });
    }
}
