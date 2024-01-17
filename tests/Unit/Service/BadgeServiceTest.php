<?php

namespace Tests\Unit\Service;

use App\Services\BadgeService;
use PHPUnit\Framework\TestCase;
use App\Models\User;
use Illuminate\Support\Collection;

// This class contains methods that test the functionality of the BadgeService.
class BadgeServiceTest extends TestCase
{
    // Tests the getBadges method.
    public function testGetBadges()
    {
        // Calls the getBadges method and stores the result in $badges.
        $badges = BadgeService::getBadges();

        // Asserts that $badges is an array.
        $this->assertIsArray($badges);

        // Asserts that the badges array contains specific keys and values.
        $expectedBadges = [
            'Beginner' => 0,
            'Intermediate' => 4,
            'Advanced' => 8,
            'Master' => 10
        ];
        $this->assertEquals($expectedBadges, $badges);
    }

    // Tests the calculateBadgeProgress method for various scenarios.
    public function testCalculateBadgeProgress()
    {
        // Defines test cases with expected results for different achievement counts.
        $testCases = [
            ['achievementCount' => 0, 'expectedResult' => ['Beginner', 'Intermediate', 4]],
            ['achievementCount' => 3, 'expectedResult' => ['Beginner', 'Intermediate', 1]],
            ['achievementCount' => 5, 'expectedResult' => ['Intermediate', 'Advanced', 3]],
            ['achievementCount' => 10, 'expectedResult' => ['Master', '', 0]]
        ];

        foreach ($testCases as $testCase) {
            // Create a mock User object.
            $user = $this->createMock(User::class);
    
            // Create a mock Collection to simulate the achievements relationship.
            $achievementsCollectionMock = $this->createMock(Collection::class);
            // Configures the mock Collection's 'count' method to return the specified number of achievements.
            $achievementsCollectionMock->method('count')->willReturn($testCase['achievementCount']);
    
            // Configures the mock User object to return the mock collection when the 'achievements' method is called.
            $user->method('achievements')->willReturn($achievementsCollectionMock);
    
            // Sanity check: Ensure the mocked achievements count method returns the expected value.
            $this->assertEquals($testCase['achievementCount'], $user->achievements()->count(), "Mock setup error: The count of achievements returned by the mock is not as expected.");
    
            // Calls calculateBadgeProgress and asserts the result matches the expected outcome.
            $result = BadgeService::calculateBadgeProgress($user);
            $this->assertEquals($testCase['expectedResult'], $result, "Failed on achievement count: " . $testCase['achievementCount']);
        }
    }
}