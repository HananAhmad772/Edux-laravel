<?php

namespace App\Services;

use App\Models\DailyProgress;
use App\Models\DailyChallenge;
use App\Models\UserProgress;
use App\Models\StudentRoadmap;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExtendedDashboardService
{
    /**
     * Get extended dashboard data including XP points, weekly activity, skill mastery, 
     * AI insights, and feedback history
     *
     * @param string $userId
     * @return array
     */
    public function getExtendedDashboardData($userId)
    {
        try {
            // Get basic dashboard data first
            $dashboardService = new DashboardService();
            $basicData = $dashboardService->getDashboardData($userId);
            
            if (!$basicData['status']) {
                return $basicData;
            }
            
            $data = $basicData['data'];
            
            // Add extended data
            $data['weekly_activity'] = $this->getWeeklyActivity($userId);
            $data['skill_mastery'] = $this->getSkillMastery($userId);
            $data['ai_insights'] = $this->getAIInsights($userId);
            $data['feedback_history'] = $this->getFeedbackHistory($userId);
            
            return [
                'status' => true,
                'message' => 'Extended dashboard data retrieved successfully',
                'data' => $data,
                'code' => 200
            ];
            
        } catch (\Exception $e) {
            Log::error('Extended dashboard data retrieval failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'status' => false,
                'message' => 'Failed to retrieve extended dashboard data: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }
    
    /**
     * Get weekly activity data
     *
     * @param string $userId
     * @return array
     */
    private function getWeeklyActivity($userId)
    {
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();
        
        // Get daily progress for the current week
        $dailyActivities = DailyProgress::where('user_id', $userId)
            ->whereBetween('progress_date', [$startDate, $endDate])
            ->orderBy('progress_date')
            ->get();
            
        $weeklyData = [];
        $totalXP = 0;
        $activeDays = 0;
        
        // Initialize all days of the week
        for ($i = 0; $i < 7; $i++) {
            $date = clone $startDate;
            $date->addDays($i);
            
            $dailyData = $dailyActivities->firstWhere('progress_date', $date->toDateString());
            
            $weeklyData[] = [
                'date' => $date->toDateString(),
                'day' => $date->format('D'),
                'xp_earned' => $dailyData ? $dailyData->xp_earned : 0,
                'time_spent_minutes' => $dailyData ? $dailyData->time_spent_minutes : 0,
                'topics_completed' => $dailyData && $dailyData->topic_completed ? 1 : 0
            ];
            
            if ($dailyData) {
                $totalXP += $dailyData->xp_earned;
                if ($dailyData->time_spent_minutes > 0) {
                    $activeDays++;
                }
            }
        }
        
        return [
            'days' => $weeklyData,
            'total_xp' => $totalXP,
            'active_days' => $activeDays,
            'completion_rate' => $activeDays > 0 ? round(($activeDays / 7) * 100, 1) : 0
        ];
    }
    
    /**
     * Get skill mastery data
     *
     * @param string $userId
     * @return array
     */
    private function getSkillMastery($userId)
    {
        // Get user progress
        $userProgress = UserProgress::where('user_id', $userId)->first();
        
        // Get latest roadmap
        $roadmap = StudentRoadmap::where('student_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$roadmap) {
            return [
                'skills' => [],
                'overall_mastery' => 0
            ];
        }
        
        // Parse roadmap JSON
        $roadmapJson = $roadmap->roadmap_json ?? [];
        $steps = $roadmapJson['steps'] ?? [];
        
        if (empty($steps)) {
            return [
                'skills' => [],
                'overall_mastery' => 0
            ];
        }
        
        // Get completed topics from daily progress
        $completedTopics = DailyProgress::where('user_id', $userId)
            ->where('topic_completed', true)
            ->pluck('topic_name')
            ->toArray();
            
        $skills = [];
        $totalTopics = 0;
        $masteredTopics = 0;
        
        // Extract skills from roadmap
        foreach ($steps as $step) {
            $topics = $step['topics'] ?? [];
            $stepSkills = $step['skills_learned'] ?? [];
            
            foreach ($stepSkills as $skill) {
                // Count how many topics for this skill have been completed
                $skillTopics = array_filter($topics, function($topic) use ($skill) {
                    // Simple matching - in a real implementation, you might want more sophisticated skill-topic mapping
                    return stripos($topic, $skill) !== false || stripos($skill, $topic) !== false;
                });
                
                $completedSkillTopics = array_filter($skillTopics, function($topic) use ($completedTopics) {
                    return in_array($topic, $completedTopics);
                });
                
                $mastery = count($skillTopics) > 0 ? 
                    round((count($completedSkillTopics) / count($skillTopics)) * 100, 1) : 0;
                
                $skills[] = [
                    'name' => $skill,
                    'mastery_percentage' => $mastery,
                    'completed_topics' => count($completedSkillTopics),
                    'total_topics' => count($skillTopics)
                ];
                
                $totalTopics += count($skillTopics);
                $masteredTopics += count($completedSkillTopics);
            }
        }
        
        $overallMastery = $totalTopics > 0 ? round(($masteredTopics / $totalTopics) * 100, 1) : 0;
        
        return [
            'skills' => $skills,
            'overall_mastery' => $overallMastery
        ];
    }
    
    /**
     * Get AI insights and motivation
     *
     * @param string $userId
     * @return array
     */
    private function getAIInsights($userId)
    {
        // Get recent activity
        $recentProgress = DailyProgress::where('user_id', $userId)
            ->whereDate('progress_date', '>=', Carbon::now()->subDays(7))
            ->orderBy('progress_date', 'desc')
            ->get();
            
        $completedChallenges = DailyChallenge::where('user_id', $userId)
            ->where('is_completed', true)
            ->count();
            
        $totalChallenges = DailyChallenge::where('user_id', $userId)
            ->count();
            
        $completionRate = $totalChallenges > 0 ? 
            round(($completedChallenges / $totalChallenges) * 100, 1) : 0;
            
        $totalTimeSpent = $recentProgress->sum('time_spent_minutes');
        $totalXP = $recentProgress->sum('xp_earned');
        
        // Generate motivational message based on user's progress
        $motivationalMessage = $this->generateMotivationalMessage(
            $completionRate, 
            $totalTimeSpent, 
            $totalXP, 
            $recentProgress->count()
        );
        
        return [
            'completion_rate' => $completionRate,
            'time_spent_hours' => round($totalTimeSpent / 60, 1),
            'xp_earned' => $totalXP,
            'challenges_completed' => $completedChallenges,
            'motivational_message' => $motivationalMessage,
            'last_7_days' => [
                'days_active' => $recentProgress->count(),
                'avg_xp_per_day' => $recentProgress->count() > 0 ? round($totalXP / $recentProgress->count()) : 0
            ]
        ];
    }
    
    /**
     * Generate motivational message based on user's progress
     *
     * @param float $completionRate
     * @param int $timeSpentMinutes
     * @param int $xpEarned
     * @param int $daysActive
     * @return string
     */
    private function generateMotivationalMessage($completionRate, $timeSpentMinutes, $xpEarned, $daysActive)
    {
        if ($daysActive == 0) {
            return "Start your learning journey today! Complete your first challenge to begin earning XP.";
        }
        
        if ($completionRate >= 80) {
            return "Excellent work! You're doing great. Keep up the fantastic progress!";
        }
        
        if ($completionRate >= 60) {
            return "Good job! You're making solid progress. Keep pushing forward!";
        }
        
        if ($xpEarned >= 500) {
            return "You're earning lots of XP! That's awesome progress. Keep it up!";
        }
        
        if ($timeSpentMinutes >= 600) { // 10 hours
            return "You've spent considerable time learning! That dedication is impressive. Continue the great work!";
        }
        
        return "You're doing good! Continue your learning journey and you'll see great results.";
    }
    
    /**
     * Get feedback history from completed challenges
     *
     * @param string $userId
     * @return array
     */
    private function getFeedbackHistory($userId)
    {
        // Get completed challenges with feedback
        $challenges = DailyChallenge::where('user_id', $userId)
            ->where('is_completed', true)
            ->whereNotNull('ai_feedback')
            ->orderBy('created_at', 'desc')
            ->limit(10) // Limit to last 10 for performance
            ->get();
            
        $history = [];
        
        foreach ($challenges as $challenge) {
            $history[] = [
                'id' => $challenge->id,
                'date' => $challenge->created_at->format('Y-m-d'),
                'topic' => $challenge->topic_name,
                'feedback' => $challenge->ai_feedback,
                'points_earned' => $challenge->points_earned,
                'time_ago' => $challenge->created_at->diffForHumans()
            ];
        }
        
        return $history;
    }
}