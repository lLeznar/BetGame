<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'JohnDoe',
            'pin' => '1234',
            'is_banker' => false,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['user' => ['id', 'name', 'is_banker'], 'token']);
                 
        $this->assertDatabaseHas('users', ['name' => 'JohnDoe', 'is_banker' => false]);
    }

    public function test_can_login()
    {
        $user = User::factory()->create([
            'name' => 'JaneSmith',
            'pin' => bcrypt('4321'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'name' => 'JaneSmith',
            'pin' => '4321',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'token']);
    }
    
    public function test_cannot_login_with_wrong_pin()
    {
        $user = User::factory()->create([
            'name' => 'JaneSmith',
            'pin' => bcrypt('4321'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'name' => 'JaneSmith',
            'pin' => '1111',
        ]);

        $response->assertStatus(401);
    }
}
