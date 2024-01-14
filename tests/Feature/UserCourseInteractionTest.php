<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Comment;
use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserCourseInteractionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_completes_lessons_writes_comments_unlocks_achievements_and_earns_badges()
    {
        $user = User::factory()->create();

        $this->simulateWatchingLessons($user, 50);
        $this->simulateWritingComments($user, 20);

        $this->dispatchBadgeEvent($user);

        $user->refresh();

        $this->assertCount(10, $user->achievements);
        $this->assertEquals('Master', $user->badge);
    }

    private function simulateWatchingLessons(User $user, int $lessonsCount)
    {
        $lessons = Lesson::factory()->count($lessonsCount)->create();
        foreach ($lessons as $lesson) {
            $user->lessons()->attach($lesson, ['watched' => true]);
            AchievementUnlocked::dispatch('50 Lessons Watched', $user);
        }
    }

    private function simulateWritingComments(User $user, int $commentsCount)
    {
        foreach (range(1, $commentsCount) as $i) {
            Comment::create(['body' => "Comment {$i}", 'user_id' => $user->id]);
            AchievementUnlocked::dispatch('20 Comments Written', $user);
        }
    }

    private function dispatchBadgeEvent(User $user)
    {
        $badgeName = $this->determineBadgeName($user->achievements()->count());
        BadgeUnlocked::dispatch($badgeName, $user);
    }

    private function determineBadgeName(int $achievementCount): string
    {
        if ($achievementCount >= 10) {
            return 'Master';
        } elseif ($achievementCount >= 8) {
            return 'Advanced';
        } elseif ($achievementCount >= 4) {
            return 'Intermediate';
        }
        return 'Beginner';
    }
}

