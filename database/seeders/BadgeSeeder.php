<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Badge;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            [
                'name' => 'First Login',
                'description' => 'Completed registration',
                'icon' => '🎉',
                'criteria_type' => 'registration',
                'criteria_value' => 1
            ],
            [
                'name' => 'Day 5 Completed',
                'description' => 'Finished first week',
                'icon' => '🔥',
                'criteria_type' => 'days_completed',
                'criteria_value' => 5
            ],
            [
                'name' => 'First Project Passed',
                'description' => 'Successfully completed first project',
                'icon' => '✅',
                'criteria_type' => 'projects_completed',
                'criteria_value' => 1
            ],
            [
                'name' => 'Consistency Champion',
                'description' => '5-day learning streak',
                'icon' => '🏆',
                'criteria_type' => 'streak',
                'criteria_value' => 5
            ],
            [
                'name' => 'Python Basics Master',
                'description' => 'Scored 90%+ in basics',
                'icon' => '🐍',
                'criteria_type' => 'quiz_score',
                'criteria_value' => 90
            ],
            [
                'name' => 'Early Bird',
                'description' => 'Learn before 8 AM',
                'icon' => '🌅',
                'criteria_type' => 'time_of_day',
                'criteria_value' => 8
            ],
            [
                'name' => 'Night Owl',
                'description' => 'Learn after 10 PM',
                'icon' => '🦉',
                'criteria_type' => 'time_of_day',
                'criteria_value' => 22
            ],
            [
                'name' => 'Speed Learner',
                'description' => 'Complete 3 days in 1 week',
                'icon' => '⚡',
                'criteria_type' => 'days_in_week',
                'criteria_value' => 3
            ],
            [
                'name' => 'Perfectionist',
                'description' => 'Score 10/10 in project',
                'icon' => '💯',
                'criteria_type' => 'project_score',
                'criteria_value' => 10
            ]
        ];

        foreach ($badges as $badge) {
            Badge::firstOrCreate(
                ['name' => $badge['name']],
                $badge
            );
        }
    }
}