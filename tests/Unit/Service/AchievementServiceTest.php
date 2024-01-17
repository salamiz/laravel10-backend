<?php

namespace Tests\Unit\Service;

use App\Services\AchievementService;
use PHPUnit\Framework\TestCase;


class AchievementServiceTest extends TestCase
{
    // testGetAchievements is a test method that checks the functionality of the getAchievements method.
    public function testGetAchievements()
    {
        // Calls the getAchievements method and stores the result in $achievements.
        $achievements = AchievementService::getAchievements();

        // Asserts that $achievements is an array.
        $this->assertIsArray($achievements);

        // Asserts that the 'Lessons Watched' key exists in the $achievements array.
        $this->assertArrayHasKey('Lessons Watched', $achievements);

        // Asserts that the 'Comments Written' key exists in the $achievements array.
        $this->assertArrayHasKey('Comments Written', $achievements);
    }

    // testCalculateNextAvailableAchievementsWithNoAchievements is a test method for calculating the next achievements when no achievements have been unlocked.
    public function testCalculateNextAvailableAchievementsWithNoAchievements()
    {
        // An empty array representing no unlocked achievements.
        $unlockedAchievements = [];

        // Calls calculateNextAvailableAchievements with an empty array and stores the result in $nextAvailable.
        $nextAvailable = AchievementService::calculateNextAvailableAchievements($unlockedAchievements);

        // Asserts that the next available achievements are the first ones in each category when no achievements are unlocked.
        $this->assertEquals(['First Lesson Watched', 'First Comment Written'], $nextAvailable);
    }

    // testCalculateNextAvailableAchievementsWithSomeAchievements checks the function when some achievements are already unlocked.
    public function testCalculateNextAvailableAchievementsWithSomeAchievements()
    {
        // An array of unlocked achievements.
        $unlockedAchievements = ['First Lesson Watched', '5 Lessons Watched', 'First Comment Written'];

        // Calls calculateNextAvailableAchievements with a list of some unlocked achievements and stores the result in $nextAvailable.
        $nextAvailable = AchievementService::calculateNextAvailableAchievements($unlockedAchievements);

        // Asserts that the next available achievements are correct based on the unlocked achievements provided.
        $this->assertEquals(['10 Lessons Watched', '3 Comments Written'], $nextAvailable);
    }

    // testCalculateNextAvailableAchievementsWithAllAchievements checks the function when all achievements are unlocked.
    public function testCalculateNextAvailableAchievementsWithAllAchievements()
    {
        // An array representing all unlocked achievements.
        $unlockedAchievements = ['First Lesson Watched', '5 Lessons Watched', '10 Lessons Watched', '25 Lessons Watched', '50 Lessons Watched', 'First Comment Written', '3 Comments Written', '5 Comments Written', '10 Comments Written', '20 Comments Written'];

        // Calls calculateNextAvailableAchievements with all achievements unlocked and stores the result in $nextAvailable.
        $nextAvailable = AchievementService::calculateNextAvailableAchievements($unlockedAchievements);

        // Asserts that there are no next available achievements when all achievements are unlocked.
        $this->assertEmpty($nextAvailable);
    }
}