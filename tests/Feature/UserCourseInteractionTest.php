<?php

namespace Tests\Feature;


// Importing necessary classes and traits
use Tests\TestCase;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Comment;
use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Listeners\UnlockAchievementListener;
use App\Listeners\UnlockBadgeListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AchievementService;
use App\Services\BadgeService;


// Define the UserCourseInteractionTest class which extends the base TestCase
class UserCourseInteractionTest extends TestCase
{
    // Using the RefreshDatabase trait to reset the database state before each test.
    use RefreshDatabase;

    /** @test */
    public function user_completes_lessons_writes_comments_unlocks_achievements_and_earns_badges()
    {
        // Create a new user using a factory
        $user = User::factory()->create();
        // Instantiate listeners for achievements and badges
        $achievementListener = new UnlockAchievementListener();
        $badgeListener = new UnlockBadgeListener();

        // Simulate user activities: watching lessons and writing comments
        $this->simulateWatchingLessons($user, 50, $achievementListener);
        $this->simulateWritingComments($user, 20, $achievementListener);

        // Determine and unlock the badge based on user achievements
        $badgeName = $this->determineBadgeName($user->achievements()->count());
        // Dispatch badge event and handle it
        $badgeEvent = new BadgeUnlocked($badgeName, $user);
        $badgeListener->handle($badgeEvent);

        // Refresh the user model to reflect changes
        $user->refresh();

        // Assert that the user has the expected number of achievements and the correct badge
        $this->assertCount(10, $user->achievements);
        $this->assertEquals('Master', $user->badge);
    }

    // Simulates the process of a user watching lessons and potentially unlocking achievements
    private function simulateWatchingLessons(User $user, int $lessonsCount, UnlockAchievementListener $listener)
    {
        // Retrieve achievements related to watching lessons
        $achievements = AchievementService::getAchievements()['Lessons Watched'];
        $watchedLessons = 0;
        $unlockedAchievements = [];

        // Iterate through each lesson
        foreach (range(1, $lessonsCount) as $i) {
            // Create a lesson and mark it as watched by the user
            $lesson = Lesson::factory()->create();
            $user->lessons()->attach($lesson, ['watched' => true]);
            $watchedLessons++;

            // Check and unlock achievements based on the number of watched lessons
            foreach ($achievements as $achievement) {
                if (!in_array($achievement, $unlockedAchievements) && $this->shouldUnlockAchievement($achievement, $watchedLessons)) {
                    // Create and dispatch an achievement unlocked event
                    $event = new AchievementUnlocked($achievement, $user);
                    $listener->handle($event);
                    $unlockedAchievements[] = $achievement;
                }
            }
        }
    }

    // Simulates the process of a user writing comments and potentially unlocking achievements
    private function simulateWritingComments(User $user, int $commentsCount, UnlockAchievementListener $listener)
    {
        // Retrieve achievements related to writing comments
        $achievements = AchievementService::getAchievements()['Comments Written'];
        $writtenComments = 0;
        $unlockedAchievements = [];

        // Iterate through each comment
        foreach (range(1, $commentsCount) as $i) {
            // Create a new comment with a dynamic body text and associate it with the user
            Comment::create(['body' => "Comment {$i}", 'user_id' => $user->id]);
            $writtenComments++;
    
            // Check and unlock achievements based on the number of written comments
            foreach ($achievements as $achievement) {
                // If the achievement hasn't been unlocked yet and the criteria is met, unlock it
                if (!in_array($achievement, $unlockedAchievements) && $this->shouldUnlockAchievement($achievement, $writtenComments)) {
                    // Create and dispatch an achievement unlocked event
                    $event = new AchievementUnlocked($achievement, $user);
                    $listener->handle($event);
                    $unlockedAchievements[] = $achievement;
                }
            }
        }
    }
    
    // Determines whether an achievement should be unlocked based on the current count
    private function shouldUnlockAchievement(string $achievement, int $count): bool
    {
        // Special handling for first-time achievements
        if ($achievement === 'First Lesson Watched' || $achievement === 'First Comment Written') {
            return $count == 1;
        }
    
        // Extract the numeric part from the achievement name, if present
        preg_match('/\d+/', $achievement, $matches);
        $number = $matches[0] ?? null;
    
        // Return false if the achievement does not contain a numeric target
        if ($number === null) {
            return false;
        }
    
        // Unlock the achievement if the count matches the target number
        return $count == $number;
    }
    
    // Determines the badge name based on the total number of achievements
    private function determineBadgeName(int $achievementCount): string
    {
        // Retrieve all available badges
        $badges = BadgeService::getBadges();
        $qualifiedBadge = 'Beginner'; // Default to the lowest badge level
    
        // Iterate over each badge to find the highest qualified badge based on achievement count
        foreach ($badges as $badge => $count) {
            // If the user's achievement count meets or exceeds the required count, update the badge
            if ($achievementCount >= $count) {
                $qualifiedBadge = $badge; // Update to a higher level badge
            }
        }
        return $qualifiedBadge;
    }
}