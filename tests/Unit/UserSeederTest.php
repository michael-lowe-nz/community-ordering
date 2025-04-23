<?php

namespace Tests\Unit;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the UserSeeder correctly adds the admin user.
     */
    public function test_user_seeder_creates_admin_user(): void
    {
        // Run the seeder
        $this->seed(UserSeeder::class);

        // Check that the user with the specified email exists in the database
        $this->assertDatabaseHas('users', [
            'email' => 'lowe.michael.nz@gmail.com',
        ]);

        // Verify that only one user was created
        $this->assertEquals(1, User::count());

        // Verify that the user has the correct name
        $user = User::where('email', 'lowe.michael.nz@gmail.com')->first();
        $this->assertEquals('Michael Lowe', $user->name);
        
        // Verify that the email is verified
        $this->assertNotNull($user->email_verified_at);
    }
}