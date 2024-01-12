<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Models\User;
use App\Events\AchievementUnlocked;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Class AchievementUnlockedTest
 *
 * This class contains unit tests for the AchievementUnlocked event.
 * It verifies if the event is dispatched correctly with the appropriate achievement name and user instance.
 *
 * The tests ensure that for each achievement unlock scenario, the correct data is being dispatched by the event.
 *
 * @package Tests\Unit\Events
 */
class AchievementUnlockedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the dispatching of the AchievementUnlocked event.
     *
     * This method tests if the AchievementUnlocked event is dispatched properly with a given
     * achievement name and user instance. It asserts that the event contains the correct data.
     *
     * @return void
     *
     * @test */
    public function testAchievementUnlockedEventDispatch()
    {
        // Prevent actual event handling to only test its dispatch.
        Event::fake();
        
        // Arrange: Create a user instance and define an achievement name.
        $user = User::factory()->create();
        $achievementName = 'First Lesson Watched';

        // Act: Dispatch the AchievementUnlocked event.
        AchievementUnlocked::dispatch($achievementName, $user);

        // Assert: The event is dispatched with the correct achievement name and user instance.
        Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($achievementName, $user) {
            return $event->achievementName === $achievementName && $event->user->id === $user->id;
        });
    }
}
