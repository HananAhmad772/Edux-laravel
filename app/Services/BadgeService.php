<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\UserBadge;
use App\Models\DailyProgress;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Log;

class BadgeService
{
    /**
     * Check and award badges based on user progress
     */
    public function checkAndAwardBadges($user)
    {
        try {
            // Get all badges
            $badges = Badge::all();
            
            foreach ($badges as $badge) {
                // Skip if user already has this badge
                $existingUserBadge = UserBadge::where('user_id', $user->id)
                    ->where('badge_id', $badge->id)
                    ->first();
                    
                if ($existingUserBadge) {
                    continue;
                }
                
                // Check if user meets the criteria for this badge
                if ($this->meetsBadgeCriteria($user, $badge)) {
                    $this->awardBadge($user, $badge);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to check and award badges: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if user meets the criteria for a specific badge
     */
    private function meetsBadgeCriteria($user, $badge)
    {
        switch ($badge->criteria_type) {
            case 'registration':
                // First Login badge - awarded on registration/login
                return true;
                
            case 'days_completed':
                // Day 5 Completed badge - check if user has completed 5 days
                $completedDays = DailyProgress::where('user_id', $user->id)
                    ->where('topic_completed', true)
                    ->distinct('progress_date')
                    ->count('progress_date');
                return $completedDays >= $badge->criteria_value;
                
            case 'projects_completed':
                // First Project Passed badge - check if user has completed projects
                // For now, we'll check if they have any completed daily progress
                $completedProjects = DailyProgress::where('user_id', $user->id)
                    ->where('topic_completed', true)
                    ->where('topic_name', 'like', '%project%')
                    ->count();
                return $completedProjects >= $badge->criteria_value;
                
            case 'streak':
                // Consistency Champion badge - check for learning streak
                return $this->hasLearningStreak($user, $badge->criteria_value);
                
            case 'quiz_score':
                // Python Basics Master badge - check quiz scores
                // This would require checking quiz results, for now we'll return false
                return false;
                
            case 'time_of_day':
                // Early Bird/Night Owl badges - check learning time
                return $this->learnedAtTime($user, $badge->criteria_value);
                
            case 'days_in_week':
                // Speed Learner badge - check days completed in a week
                $completedDaysThisWeek = DailyProgress::where('user_id', $user->id)
                    ->where('topic_completed', true)
                    ->where('progress_date', '>=', now()->startOfWeek())
                    ->distinct('progress_date')
                    ->count('progress_date');
                return $completedDaysThisWeek >= $badge->criteria_value;
                
            case 'project_score':
                // Perfectionist badge - check project scores
                // This would require checking project evaluation scores, for now we'll return false
                return false;
                
            default:
                return false;
        }
    }
    
    /**
     * Check if user has a learning streak of specified days
     */
    private function hasLearningStreak($user, $days)
    {
        // Get consecutive days of learning
        $consecutiveDays = 0;
        $currentDate = now();
        
        for ($i = 0; $i < 30; $i++) { // Check up to 30 days back
            $dateToCheck = $currentDate->copy()->subDays($i);
            $learningOnDate = DailyProgress::where('user_id', $user->id)
                ->where('progress_date', $dateToCheck->toDateString())
                ->where('topic_completed', true)
                ->exists();
                
            if ($learningOnDate) {
                $consecutiveDays++;
            } else {
                break;
            }
        }
        
        return $consecutiveDays >= $days;
    }
    
    /**
     * Check if user learned at a specific time of day
     */
    private function learnedAtTime($user, $hour)
    {
        if ($hour < 12) {
            // Early bird - check for learning before noon
            $earlyLearning = DailyProgress::where('user_id', $user->id)
                ->where('topic_completed', true)
                ->whereTime('created_at', '<', '12:00:00')
                ->exists();
            return $earlyLearning;
        } else {
            // Night owl - check for learning after 10 PM
            $lateLearning = DailyProgress::where('user_id', $user->id)
                ->where('topic_completed', true)
                ->whereTime('created_at', '>', '22:00:00')
                ->exists();
            return $lateLearning;
        }
    }
    
    /**
     * Award a badge to a user
     */
    private function awardBadge($user, $badge)
    {
        try {
            UserBadge::create([
                'user_id' => $user->id,
                'badge_id' => $badge->id,
                'earned_at' => now()
            ]);
            
            Log::info("Badge awarded: {$badge->name} to user {$user->id}");
        } catch (\Exception $e) {
            Log::error("Failed to award badge {$badge->name} to user {$user->id}: " . $e->getMessage());
        }
    }
}