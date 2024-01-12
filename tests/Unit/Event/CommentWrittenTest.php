<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Models\Comment;
use App\Events\CommentWritten;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test Class for CommentWritten Event
 *
 * This class contains unit tests for CommentWritten event.
 * It checks if the event is dispatched correctly with the appropriate Comment instance.
 *
 * Uses Laravel's built-in testing functionality along with model factories
 * to simulate event dispatching.
 * @package Tests\Unit\Events
 */
class CommentWrittenTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test to ensure the CommentWritten event dispatches correctly.
     *
     * This test creates a comment instance using the Comment factory,
     * dispatches the CommentWritten event, and asserts that the event
     * is dispatched correctly with the appropriate Comment instance.
     *
     * @return void
     *
     * @test */
    public function it_dispatches_comment_written_event()
    {
        // Prevent the actual event handling to focus on the dispatch.
        Event::fake();

        // Arrange: Create a comment instance using the factory.
        $comment = Comment::factory()->create();

        // Act: Dispatch the CommentWritten event with the comment instance.
        CommentWritten::dispatch($comment);

        // Assert: Check that the CommentWritten event was dispatched with the correct comment.
        Event::assertDispatched(CommentWritten::class, function ($event) use ($comment) {
            return $event->comment->id === $comment->id;
        });
    }
}
