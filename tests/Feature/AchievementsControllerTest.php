<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Achievement;
use App\Services\AchievementService;
use App\Services\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementsControllerTest extends TestCase
{
    use RefreshDatabase;

    // Test user without achievements
    public function testUserWithNoAchievements()
    {
        // Arrange: Create a user without any achievements
        $user = User::factory()->create();

        // Act: Make a request to the achievements endpoint
        $response = $this->getJson("/users/{$user->id}/achievements");

        // Assert: Check if the response structure is as expected
        $response->assertStatus(200)
                 ->assertJson([
                     'unlocked_achievements' => [],
                     'next_available_achievements' => ['First Lesson Watched', 'First Comment Written'], // Assuming these are the first achievements
                     'current_badge' => 'Beginner',
                     'next_badge' => 'Intermediate',
                     'remaining_to_unlock_next_badge' => 4
                 ]);
    }

    // Test with non existent user
    public function testAccessWithNonExistentUser()
    {
        // Create invalid user
        $invalidId = 13579;
        // Simulating a request to the controller with a non-existent user ID
        $response = $this->getJson('/users/{$invalidId}/achievements');

        // Asserting that the response status is 404 Not Found
        $response->assertStatus(404);
    }

    // Test user with some achievements
    public function testUserWithSomeAchievements()
    {
        // Arrange: Create a user
        $user = User::factory()->create();

        // Fetch all the achievements from the AchievementService
        $allAchievements = AchievementService::getAchievements();
        $assignedAchievements = [];
        foreach ($allAchievements as $category => $achievements) {
            $assignedAchievements[] = $achievements[0]; // Get the first achievement from each category
        }

        // Create these achievements and assign them to the user
        foreach ($assignedAchievements as $achievementName) {
            $achievement = Achievement::factory()->create(['name' => $achievementName]);
            $user->achievements()->attach($achievement);
        }

        // Calculate the expected next available achievements
        $expectedNextAvailable = [];
        foreach ($allAchievements as $category => $achievements) {
            foreach ($achievements as $achievement) {
                if (!in_array($achievement, $assignedAchievements)) {
                    $expectedNextAvailable[] = $achievement;
                    break; // Add only the next achievement in line per category
                }
            }
        }

        // Determine expected badge based on the number of achievements unlocked
        $badges = BadgeService::getBadges();
        $achievementCount = count($assignedAchievements);
        $currentBadge = 'Beginner';
        $nextBadge = null;
        $remainingToUnlockNextBadge = 0;
        foreach ($badges as $badge => $threshold) {
            if ($achievementCount < $threshold) {
                $nextBadge = $badge;
                $remainingToUnlockNextBadge = $threshold - $achievementCount;
                break;
            }
            $currentBadge = $badge;
        }

        // Act: Make a request to the achievements endpoint
        $response = $this->getJson("/users/{$user->id}/achievements");

        // Assert: Check if the response structure is as expected
        $response->assertStatus(200)
                ->assertJson([
                    'unlocked_achievements' => $assignedAchievements,
                    'next_available_achievements' => $expectedNextAvailable,
                    // Assertions for badge progress
                    'current_badge' => $currentBadge,
                    'next_badge' => $nextBadge,
                    'remaining_to_unlock_next_badge' => $remainingToUnlockNextBadge
                ]);
    }


    // Test user with all achievements
    public function testUserWithAllAchievementsUnlocked()
    {
        // Arrange: Create a user and assign all achievements to them
        $user = User::factory()->create();

        // Fetch all possible achievements from the AchievementService
        $allAchievements = collect(AchievementService::getAchievements())->flatten();

        // Create achievements and assign them to the user
        foreach ($allAchievements as $achievementName) {
            $achievement = Achievement::factory()->create(['name' => $achievementName]);
            $user->achievements()->attach($achievement);
        }

        // Act: Make a request to the achievements endpoint
        $response = $this->getJson("/users/{$user->id}/achievements");

        // Assert: Check if the response structure indicates all achievements unlocked
        $response->assertStatus(200)
                ->assertJson([
                    'unlocked_achievements' => $allAchievements->all(),
                    'next_available_achievements' => [], // Expecting an empty array
                    // Assertions for badge progress
                    'current_badge' => 'Master', // Assuming 'Master' is the highest badge
                    'next_badge' => null, // No next badge since all achievements are unlocked
                    'remaining_to_unlock_next_badge' => 0 // No more achievements to unlock
                ]);
    }
}
