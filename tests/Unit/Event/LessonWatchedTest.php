<?php
/**
 * Location: tests/Unit/Events/LessonWatchedTest.php
 * Purpose: Tests the dispatching of the LessonWatched event.
 * Dependencies: App\Models\Lesson, App\Models\User, App\Events\LessonWatched
 */

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Events\LessonWatched;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Class LessonWatchedTest
 *
 * Purpose: To ensure that the LessonWatched event is dispatched correctly and contains the appropriate Lesson and User instances.
 * Details: Uses RefreshDatabase to ensure a clean database state for each test.
 * @package Tests\Unit\Events
 */
class LessonWatchedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the LessonWatched event is dispatched with the correct data.
     * 
     * Scenario: When a lesson is watched by a user, the LessonWatched event should be dispatched.
     * Expected Behavior: The event should contain the correct Lesson and User instances.
     * @return void
     * @test */
    public function it_dispatches_lesson_watched_event()
    {
        Event::fake();

        // Arrange: Create a lesson and a user using the factories
        $lesson = Lesson::factory()->create();
        $user = User::factory()->create();

        // Act: Dispatch the LessonWatched event
        LessonWatched::dispatch($lesson, $user);

        // Assert: Check if the LessonWatched event was dispatched with the correct lesson and user
        Event::assertDispatched(LessonWatched::class, function ($event) use ($lesson, $user) {
            return $event->lesson->id === $lesson->id && $event->user->id === $user->id;
        });
    }
}
