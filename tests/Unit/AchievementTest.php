<?php

// tests/Unit/AchievementTest.php

namespace Tests\Unit;

use App\Events\AchievementUnlocked;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class AchievementTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function achievement_unlocked_event_dispatched_and_handled_correctly()
    {
        // Arrange
        Event::fake();
        $user = User::factory()->create();

        // Act
        event(new AchievementUnlocked('First Lesson Watched', $user));

        // Assert
        Event::assertDispatched(AchievementUnlocked::class, function ($event) use ($user) {
            return $event->achievementName === 'First Lesson Watched' && $event->user->id === $user->id;
        });

        // Optionally, you can assert that the listener was called
        Event::assertDispatched(AchievementUnlocked::class, 1);
    }
}

