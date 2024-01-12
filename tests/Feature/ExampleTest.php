<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase; // Add this line

use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase; // Uncomment this line
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $user = User::factory()->create();
        
        $response = $this->get("/users/{$user->id}/achievements");

        $response->assertStatus(200);
    }
}
