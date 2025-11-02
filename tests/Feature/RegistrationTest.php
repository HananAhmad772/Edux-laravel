<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_and_receive_token()
    {
        $registrationData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'student',
            'dob' => '2000-01-01',
            'gender' => 'male',
            'class_year' => '2024',
            'institute' => 'Test University',
            'bio' => 'Test bio'
        ];

        $response = $this->postJson('/api/auth/signup', $registrationData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'User registered successfully'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'user' => [
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                            'phone',
                            'user_type',
                            'status',
                            'student_profile'
                        ],
                        'token'
                    ]
                ]);

        // Verify that the token is present and not empty
        $responseData = $response->json();
        $this->assertArrayHasKey('token', $responseData['data']);
        $this->assertNotEmpty($responseData['data']['token']);
        
        // Verify user data
        $this->assertEquals('John', $responseData['data']['user']['first_name']);
        $this->assertEquals('Doe', $responseData['data']['user']['last_name']);
        $this->assertEquals('john.doe@example.com', $responseData['data']['user']['email']);
    }

    public function test_company_can_register_and_receive_token()
    {
        $registrationData = [
            'first_name' => 'Tech',
            'last_name' => 'Corp',
            'email' => 'info@techcorp.com',
            'phone' => '0987654321',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'company',
            'company_size' => '50-100',
            'industry' => 'Technology',
            'bio' => 'Leading tech company',
            'website_link' => 'https://techcorp.com',
            'location' => 'San Francisco, CA'
        ];

        $response = $this->postJson('/api/auth/signup', $registrationData);

        $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => 'User registered successfully'
                ])
                ->assertJsonStructure([
                    'data' => [
                        'user' => [
                            'id',
                            'first_name',
                            'last_name',
                            'email',
                            'phone',
                            'user_type',
                            'status',
                            'company_profile'
                        ],
                        'token'
                    ]
                ]);

        // Verify that the token is present and not empty
        $responseData = $response->json();
        $this->assertArrayHasKey('token', $responseData['data']);
        $this->assertNotEmpty($responseData['data']['token']);
    }
}