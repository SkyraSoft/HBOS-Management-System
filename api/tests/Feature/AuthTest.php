<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Business;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_register_business()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'business_name' => 'Acme Corp',
            'name' => 'John Doe',
            'email' => 'john@acme.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'user' => ['id', 'name', 'email', 'business_id'],
                     'business' => ['id', 'name'],
                     'access_token',
                     'token_type'
                 ]);

        $this->assertDatabaseHas('businesses', ['name' => 'Acme Corp']);
        $this->assertDatabaseHas('users', ['email' => 'john@acme.com']);
    }

    public function test_user_can_login()
    {
        $business = Business::create(['name' => 'Test Business']);
        $user = User::factory()->create([
            'business_id' => $business->id,
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['access_token', 'user', 'token_type']);
    }

    public function test_user_cannot_access_protected_routes_without_token()
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }
}
