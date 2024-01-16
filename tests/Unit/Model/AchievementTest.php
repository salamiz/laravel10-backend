<?php
namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Achievement;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_checks_achievement_names_from_service()
    {
        // Retrieve achievements from the service
        $achievementGroups = AchievementService::getAchievements();

        foreach ($achievementGroups as $group) {
            foreach ($group as $achievementName) {
                // Create a new Achievement instance with the name from the service
                $achievement = Achievement::create(['name' => $achievementName]);

                // Assert that the achievement's name matches the one provided by the service
                $this->assertEquals($achievementName, $achievement->name);
            }
        }
    }

    /** @test */
    public function it_can_belong_to_many_users()
    {
        // Create a user
        $user = User::factory()->create();

        // Retrieve achievements from the service
        $achievementGroups = AchievementService::getAchievements();
        $firstGroupName = array_key_first($achievementGroups);
        $firstAchievementName = $achievementGroups[$firstGroupName][0];

        // Create an achievement instance with the first name from the service
        $achievement = Achievement::create(['name' => $firstAchievementName]);

        // Attach the achievement to the user
        $user->achievements()->attach($achievement);

        // Assert that the achievement is correctly associated with the user
        $this->assertTrue($user->achievements->contains($achievement));
    }
}
