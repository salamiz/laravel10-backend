<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Comment;
use App\Models\Achievement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase; // Ensures each test has a fresh database state.

    /** @test */
    public function it_has_comments()
    {
        // Create a user and a comment related to that user.
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        // Assert that the comment is correctly associated with the user.
        $this->assertTrue($user->comments->contains($comment));
    }

    /** @test */
    public function it_has_lessons()
    {
        // Create a user and a lesson, then attach the lesson to the user.
        $user = User::factory()->create();
        $lesson = Lesson::factory()->create();
        $user->lessons()->attach($lesson);

        // Assert that the lesson is correctly associated with the user.
        $this->assertTrue($user->lessons->contains($lesson));
    }

    /** @test */
    public function it_has_watched_lessons()
    {
        // Create a user and a lesson, then mark the lesson as watched by the user.
        $user = User::factory()->create();
        $lesson = Lesson::factory()->create();
        $user->lessons()->attach($lesson, ['watched' => true]);

        // Assert that the watched lesson is correctly associated with the user.
        $this->assertTrue($user->watched->contains($lesson));
    }

    /** @test */
    public function it_has_achievements()
    {
        // Create a user and an achievement, then attach the achievement to the user.
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create();

        // Attach the achievement to the user.
        $user->achievements()->attach($achievement);

        // Assert that the achievement is correctly associated with the user.
        $this->assertTrue($user->achievements->contains($achievement));
    }

    /** @test */
    public function it_can_fill_the_badge_attribute()
    {
        // Create a user with a specific badge.
        $user = User::factory()->create(['badge' => 'Advanced']);
        
        // Assert that the badge attribute is correctly set and saved in the model.
        $this->assertEquals('Advanced', $user->badge);
    }
}
