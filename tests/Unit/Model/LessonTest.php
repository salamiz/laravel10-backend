<?php
namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        // Create an array with sample data for the Lesson model.
        $lessonData = [
            'title' => 'Sample Lesson Title',
        ];

        // Create a new Lesson instance with the sample data.
        $lesson = Lesson::create($lessonData);

        // Assert that the lesson's attributes were correctly saved.
        $this->assertEquals($lessonData['title'], $lesson->title);
    }
}
