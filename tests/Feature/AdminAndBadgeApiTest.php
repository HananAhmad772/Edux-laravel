<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\StudentProfile;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAndBadgeApiTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        $admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin@123'),
            'status' => 'approved',
            'is_admin' => true,
        ]);

        return $admin;
    }

    public function test_admin_routes_work(): void
    {
        $admin = $this->createAdminUser();
        $student = User::factory()->create([
            'first_name' => 'Student',
            'last_name' => 'One',
            'email' => 'student.one@example.com',
            'status' => 'approved',
        ]);

        StudentProfile::create([
            'user_id' => $student->id,
            'dob' => '2000-01-01',
            'gender' => 'male',
            'class_year' => '2024',
            'institute' => 'Test University',
            'major_subject' => 'Computer Science',
            'current_skill_level' => 'Beginner',
            'main_goal' => 'Get a job',
        ]);

        $token = $admin->createToken('admin-token')->plainTextToken;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];

        $this->withHeaders($headers)->postJson('/api/admin/createUser', [
            'first_name' => 'Created',
            'last_name' => 'User',
            'email' => 'created@example.com',
            'phone' => '15550000999',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'dob' => '2000-01-01',
            'gender' => 'female',
            'class_year' => '2024',
            'institute' => 'Test University',
            'bio' => 'Test bio',
        ])->assertCreated()
          ->assertJson([
              'success' => true,
              'message' => 'User registered successfully',
          ]);

        $this->withHeaders($headers)->getJson('/api/admin/all-users')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Users fetched successfully',
            ]);

        $this->withHeaders($headers)->getJson('/api/admin/user/' . $student->id)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User fetched successfully',
            ]);

        $this->withHeaders($headers)->postJson('/api/admin/update-status/' . $student->id, [
            'status' => 'approved',
        ])->assertOk()
          ->assertJson([
              'success' => true,
              'message' => 'User status updated successfully',
          ]);

        $this->withHeaders($headers)->postJson('/api/admin/delete-account/' . $student->id, [
            'ids' => [$student->id],
        ])->assertOk()
          ->assertJson([
              'success' => true,
          ]);

        $this->withHeaders($headers)->getJson('/api/admin/all-deleted-users')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Deleted users fetched successfully',
            ]);

        $this->withHeaders($headers)->postJson('/api/admin/restore-account/' . $student->id, [
            'ids' => [$student->id],
        ])->assertOk()
          ->assertJson([
              'success' => true,
          ]);
    }

    public function test_badge_routes_work(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Badge',
            'last_name' => 'User',
            'email' => 'badge@example.com',
            'status' => 'approved',
        ]);

        StudentProfile::create([
            'user_id' => $user->id,
            'dob' => '2000-01-01',
            'gender' => 'female',
            'class_year' => '2024',
            'institute' => 'Test University',
            'major_subject' => 'Computer Science',
            'current_skill_level' => 'Beginner',
            'main_goal' => 'Get a job',
        ]);

        $badge = Badge::create([
            'name' => 'First Login',
            'description' => 'Completed registration',
            'icon' => 'tada',
            'criteria_type' => 'days_completed',
            'criteria_value' => 1,
        ]);

        $token = $user->createToken('badge-token')->plainTextToken;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];

        $this->withHeaders($headers)->getJson('/api/badges')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Badges retrieved successfully',
            ]);

        $this->withHeaders($headers)->postJson('/api/badges/award', [
            'badge_id' => $badge->id,
        ])->assertOk()
          ->assertJson([
              'success' => true,
              'message' => 'Badge awarded successfully',
          ]);

        $this->withHeaders($headers)->getJson('/api/badges/user')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'User badges retrieved successfully',
            ]);
    }
}