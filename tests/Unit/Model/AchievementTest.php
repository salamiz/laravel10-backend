<?php
namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Achievement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        // Create a user and an achievement related to that user.
        $user = User::factory()->create();
        $achievementData = [
            'user_id' => $user->id,
            'name' => 'Sample Achievement',
        ];

        // Create a new Achievement instance with the sample data.
        $achievement = Achievement::create($achievementData);

        // Assert that the achievement's attributes were correctly saved.
        $this->assertEquals($achievementData['user_id'], $achievement->user_id);
        $this->assertEquals($achievementData['name'], $achievement->name);
    }

    /** @test */
    public function it_belongs_to_a_user()
    {
        // Create a user and an achievement related to that user.
        $user = User::factory()->create();
        $achievement = Achievement::factory()->create(['user_id' => $user->id]);

        // Assert that the achievement is correctly associated with the user.
        $this->assertInstanceOf(User::class, $achievement->user);
        $this->assertEquals($user->id, $achievement->user->id);
    }
}
