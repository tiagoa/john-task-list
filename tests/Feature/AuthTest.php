<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register(): void
    {
        $response = $this->postJson('/api/register');
        $response->assertStatus(422);

        $response =$this->registerUser();
        $response->assertOk();

        $response = $this->postJson('/api/register', ['name' => 'John', 'email' => $this->email, 'password' => $this->password]);
        $response->assertStatus(422);
    }

    public function test_login(): void
    {
        $this->registerUser();
        $response = $this->login();
        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('access_token')->has('token_type')
        );
    }
}
