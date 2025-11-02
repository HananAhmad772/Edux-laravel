<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => bcrypt('Admin@123'),
            'user_type' => 'admin',
            'status' => 'approved',
            'is_admin' => true,
    ]);

        User::create([
                    'first_name' => 'Student',
                    'last_name' => 'Test',
                    'email' => 'test@student.com',
                    'password' => bcrypt('1234567890'),
                    'user_type' => 'student',
                    'status' => 'approved',
                    'is_admin' => false,
            ]);

        User::create([
                    'first_name' => 'Company',
                    'last_name' => 'Test',
                    'email' => 'test@company.com',
                    'password' => bcrypt('1234567890'),
                    'user_type' => 'company',
                    'status' => 'approved',
                    'is_admin' => false,
            ]);

            User::create([
                    'first_name' => 'Jobseeker',
                    'last_name' => 'Test',
                    'email' => 'test@jobseeker.com',
                    'password' => bcrypt('1234567890'),
                    'user_type' => 'job-seeker',
                    'status' => 'approved',
                    'is_admin' => false,
            ]);

    }
}
