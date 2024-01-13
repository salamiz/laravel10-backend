<?php
namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        // Create a user and a comment related to that user.
        $user = User::factory()->create();
        $commentData = [
            'body' => 'This is a test comment.',
            'user_id' => $user->id
        ];

        // Create a new Comment instance with the sample data.
        $comment = Comment::create($commentData);

        // Assert that the comment's attributes were correctly saved.
        $this->assertEquals($commentData['body'], $comment->body);
        $this->assertEquals($commentData['user_id'], $comment->user_id);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        // Create a user and a comment related to that user.
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        // Assert that the comment is correctly associated with the user.
        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertEquals($user->id, $comment->user->id);
    }
}
