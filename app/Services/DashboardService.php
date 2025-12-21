<?php

namespace App\Services;

use App\Models\DailyProgress;
use App\Models\UserProgress;
use App\Models\StudentRoadmap;
use App\Models\StudentQuiz;
use App\Services\BadgeService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardService
{
    protected $badgeService;

    public function __construct()
    {
        $this->badgeService = new BadgeService();
    }

    /**
     * Get all dashboard data for a user
     *
     * @param string $userId
     * @return array
     */
    public function getDashboardData($userId)
    {
        try {
            // Check if student profile exists
            $studentProfile = \App\Models\StudentProfile::where('user_id', $userId)->first();
            
            if (!$studentProfile) {
                return [
                    'status' => false,
                    'message' => 'Please complete your profile setup first. Go to Profile Setup to continue.',
                    'code' => 403,
                    'profile_incomplete' => true
                ];
            }
            
            // Check if profile has required fields
            $requiredFields = ['major_subject', 'current_skill_level', 'main_goal'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (empty($studentProfile->$field) || trim($studentProfile->$field) === '') {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                return [
                    'status' => false,
                    'message' => 'Please complete your profile setup first. Missing required fields: ' . implode(', ', $missingFields),
                    'code' => 403,
                    'profile_incomplete' => true,
                    'missing_fields' => $missingFields
                ];
            }
            
            // Get user progress
            $userProgress = UserProgress::where('user_id', $userId)->first();
            
            // Get latest roadmap
            $roadmap = StudentRoadmap::where('student_id', $userId)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$roadmap) {
                return [
                    'status' => true,
                    'message' => 'Profile complete. Please generate a roadmap to get started.',
                    'data' => [
                        'current_week' => [
                            'week_number' => 0,
                            'week_name' => 'Not Started',
                            'step_title' => 'Generate Roadmap',
                            'current_day' => 0,
                            'total_days' => 0
                        ],
                        'xp' => 0,
                        'streak' => 0,
                        'last_project_score' => null,
                        'today_topic' => null,
                        'yesterday_topic' => null,
                        'roadmap_progress' => [
                            'percentage' => 0,
                            'completed_steps' => 0,
                            'total_steps' => 0,
                            'completed_topics' => 0,
                            'total_topics' => 0,
                            'steps' => []
                        ],
                        'ai_recommendation' => 'Complete your profile and generate a roadmap to start your learning journey!'
                    ],
                    'code' => 200,
                    'roadmap_needed' => true
                ];
            }
            
            // Parse roadmap JSON
            $roadmapJson = $roadmap->roadmap_json ?? [];
            $steps = $roadmapJson['steps'] ?? [];
            
            // Get current week info
            $currentWeekInfo = $this->getCurrentWeekInfo($userProgress, $steps);
            
            // Get today's and yesterday's topics
            $topics = $this->getTodayYesterdayTopics($userProgress, $steps);
            
            // Calculate XP
            $xp = $this->calculateXP($userId);
            
            // Calculate streak
            $streak = $this->calculateStreak($userId);
            
            // Get last project score
            $lastProjectScore = $this->getLastProjectScore($userId);
            
            // Calculate roadmap progress
            $roadmapProgress = $this->calculateRoadmapProgress($userId, $steps, $userProgress);
            
            // Generate AI recommendation
            $aiRecommendation = $this->generateAIRecommendation($userId, $userProgress, $steps, $topics);
            
            return [
                'status' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'current_week' => $currentWeekInfo,
                    'xp' => $xp,
                    'streak' => $streak,
                    'last_project_score' => $lastProjectScore,
                    'today_topic' => $topics['today'],
                    'yesterday_topic' => $topics['yesterday'],
                    'roadmap_progress' => $roadmapProgress,
                    'ai_recommendation' => $aiRecommendation
                ],
                'code' => 200
            ];
            
        } catch (\Exception $e) {
            Log::error('Dashboard data retrieval failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'status' => false,
                'message' => 'Failed to retrieve dashboard data: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }
    
    /**
     * Get current week information
     *
     * @param UserProgress|null $userProgress
     * @param array $steps
     * @return array
     */
    private function getCurrentWeekInfo($userProgress, $steps)
    {
        if (!$userProgress || !$userProgress->current_step) {
            return [
                'week_number' => 1,
                'week_name' => 'Week 1–2',
                'step_title' => 'Getting Started',
                'current_day' => 1,
                'total_days' => 14
            ];
        }
        
        // Find current step in roadmap
        $currentStepIndex = -1;
        foreach ($steps as $index => $step) {
            if (($step['duration'] ?? '') === $userProgress->current_step) {
                $currentStepIndex = $index;
                break;
            }
        }
        
        if ($currentStepIndex === -1) {
            $currentStepIndex = 0;
        }
        
        $currentStep = $steps[$currentStepIndex] ?? $steps[0] ?? [];
        $weekName = $currentStep['duration'] ?? 'Week 1–2';
        $stepTitle = $currentStep['title'] ?? 'Getting Started';
        
        // Calculate current day based on topic index (assuming ~2 days per topic)
        $currentDay = (($currentStepIndex * 6) + $userProgress->current_topic_index) * 2;
        $totalDays = count($steps) * 6 * 2; // 6 topics per step, ~2 days per topic
        
        return [
            'week_number' => $currentStepIndex + 1,
            'week_name' => $weekName,
            'step_title' => $stepTitle,
            'current_day' => $currentDay,
            'total_days' => $totalDays
        ];
    }
    
    /**
     * Get today's and yesterday's topics
     *
     * @param UserProgress|null $userProgress
     * @param array $steps
     * @return array
     */
    private function getTodayYesterdayTopics($userProgress, $steps)
    {
        $topics = [
            'today' => null,
            'yesterday' => null
        ];
        
        if (!$userProgress || !$userProgress->current_step) {
            return $topics;
        }
        
        // Find current step
        $currentStep = null;
        foreach ($steps as $step) {
            if (($step['duration'] ?? '') === $userProgress->current_step) {
                $currentStep = $step;
                break;
            }
        }
        
        if (!$currentStep) {
            return $topics;
        }
        
        $currentTopicIndex = $userProgress->current_topic_index;
        $topicsList = $currentStep['topics'] ?? [];
        
        // Today's topic
        if (isset($topicsList[$currentTopicIndex - 1])) {
            $topics['today'] = [
                'topic' => $topicsList[$currentTopicIndex - 1],
                'topic_index' => $currentTopicIndex,
                'step' => $userProgress->current_step,
                'step_title' => $currentStep['title'] ?? ''
            ];
        }
        
        // Yesterday's topic (previous topic in same step, or last topic of previous step)
        $yesterdayTopicIndex = $currentTopicIndex - 1;
        if ($yesterdayTopicIndex >= 1 && isset($topicsList[$yesterdayTopicIndex - 1])) {
            $topics['yesterday'] = [
                'topic' => $topicsList[$yesterdayTopicIndex - 1],
                'topic_index' => $yesterdayTopicIndex,
                'step' => $userProgress->current_step,
                'step_title' => $currentStep['title'] ?? ''
            ];
        } else if ($yesterdayTopicIndex === 0) {
            // Get last topic of previous step
            $prevStepIndex = -1;
            foreach ($steps as $index => $step) {
                if (($step['duration'] ?? '') === $userProgress->current_step) {
                    $prevStepIndex = $index - 1;
                    break;
                }
            }
            
            if ($prevStepIndex >= 0 && isset($steps[$prevStepIndex])) {
                $prevStep = $steps[$prevStepIndex];
                $prevTopics = $prevStep['topics'] ?? [];
                if (!empty($prevTopics)) {
                    $topics['yesterday'] = [
                        'topic' => end($prevTopics),
                        'topic_index' => 6,
                        'step' => $prevStep['duration'] ?? '',
                        'step_title' => $prevStep['title'] ?? ''
                    ];
                }
            }
        }
        
        return $topics;
    }
    
    /**
     * Calculate total XP from daily progress
     *
     * @param string $userId
     * @return int
     */
    private function calculateXP($userId)
    {
        return DailyProgress::where('user_id', $userId)
            ->sum('xp_earned');
    }
    
    /**
     * Calculate current streak (consecutive days with activity)
     *
     * @param string $userId
     * @return int
     */
    private function calculateStreak($userId)
    {
        $today = Carbon::today();
        $streak = 0;
        $checkDate = $today;
        
        // Check backwards from today
        while (true) {
            $hasProgress = DailyProgress::where('user_id', $userId)
                ->whereDate('progress_date', $checkDate->toDateString())
                ->where('time_spent_minutes', '>', 0)
                ->exists();
            
            if ($hasProgress) {
                $streak++;
                $checkDate = $checkDate->subDay();
            } else {
                // If today has no progress, don't count it
                if ($checkDate->isToday()) {
                    // Check yesterday
                    $checkDate = $checkDate->subDay();
                    continue;
                }
                break;
            }
        }
        
        return $streak;
    }
    
    /**
     * Get last project/quiz score
     *
     * @param string $userId
     * @return array|null
     */
    private function getLastProjectScore($userId)
    {
        $lastQuiz = StudentQuiz::where('student_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if (!$lastQuiz) {
            return null;
        }
        
        $score = (float)($lastQuiz->score ?? 0);
        
        return [
            'score' => (int)$score,
            'max_score' => 10,
            'passed' => $score >= 7,
            'date' => $lastQuiz->created_at->format('Y-m-d')
        ];
    }
    
    /**
     * Calculate roadmap progress percentage
     *
     * @param string $userId
     * @param array $steps
     * @param UserProgress|null $userProgress
     * @return array
     */
    private function calculateRoadmapProgress($userId, $steps, $userProgress)
    {
        if (empty($steps)) {
            return [
                'percentage' => 0,
                'completed_steps' => 0,
                'total_steps' => 0,
                'completed_topics' => 0,
                'total_topics' => 0,
                'steps' => []
            ];
        }
        
        $totalSteps = count($steps);
        $totalTopics = $totalSteps * 6; // 6 topics per step
        
        // Get completed topics from daily progress
        $completedTopics = DailyProgress::where('user_id', $userId)
            ->where('topic_completed', true)
            ->distinct()
            ->count('topic_name');
        
        // Calculate completed steps (all 6 topics completed)
        $completedSteps = 0;
        $stepProgress = [];
        
        foreach ($steps as $index => $step) {
            $stepName = $step['duration'] ?? "Week " . ($index + 1);
            $stepTopics = $step['topics'] ?? [];
            
            // Count completed topics in this step
            $stepCompletedTopics = DailyProgress::where('user_id', $userId)
                ->where('step_name', $stepName)
                ->where('topic_completed', true)
                ->distinct()
                ->count('topic_name');
            
            $stepTotalTopics = count($stepTopics);
            $stepPercentage = $stepTotalTopics > 0 ? ($stepCompletedTopics / $stepTotalTopics) * 100 : 0;
            
            // Determine status
            $status = 'pending';
            if ($stepPercentage >= 100) {
                $status = 'completed';
                $completedSteps++;
            } else if ($stepPercentage > 0 || ($userProgress && $userProgress->current_step === $stepName)) {
                $status = 'in-progress';
            }
            
            $stepProgress[] = [
                'week' => $index + 1,
                'week_name' => $stepName,
                'status' => $status,
                'progress' => round($stepPercentage, 1)
            ];
        }
        
        $overallPercentage = $totalTopics > 0 ? ($completedTopics / $totalTopics) * 100 : 0;
        
        return [
            'percentage' => round($overallPercentage, 1),
            'completed_steps' => $completedSteps,
            'total_steps' => $totalSteps,
            'completed_topics' => $completedTopics,
            'total_topics' => $totalTopics,
            'steps' => $stepProgress
        ];
    }
    
    /**
     * Generate AI recommendation based on progress
     *
     * @param string $userId
     * @param UserProgress|null $userProgress
     * @param array $steps
     * @param array $topics
     * @return string
     */
    private function generateAIRecommendation($userId, $userProgress, $steps, $topics)
    {
        // Get recent progress
        $recentProgress = DailyProgress::where('user_id', $userId)
            ->whereDate('progress_date', '>=', Carbon::now()->subDays(3))
            ->orderBy('progress_date', 'desc')
            ->get();
        
        // Check if user is struggling (low completion rate)
        $totalTopicsAttempted = $recentProgress->count();
        $completedTopics = $recentProgress->where('topic_completed', true)->count();
        $completionRate = $totalTopicsAttempted > 0 ? ($completedTopics / $totalTopicsAttempted) * 100 : 0;
        
        // Check streak
        $streak = $this->calculateStreak($userId);
        
        // Generate recommendation based on context
        if ($completionRate < 50 && $totalTopicsAttempted > 0) {
            $yesterdayTopic = $topics['yesterday']['topic'] ?? 'previous topics';
            return "You struggled with {$yesterdayTopic} — let's review before continuing!";
        } else if ($streak === 0) {
            return "Start your learning streak today! Complete today's topic to begin.";
        } else if ($streak >= 7) {
            return "Amazing {$streak}-day streak! Keep up the excellent work!";
        } else if ($completionRate >= 80) {
            return "You're doing great! Consider moving to the next topic to maintain momentum.";
        } else {
            $todayTopic = $topics['today']['topic'] ?? 'today\'s topic';
            return "Focus on mastering {$todayTopic} today. Take your time and practice!";
        }
    }
    
    /**
     * Record or update daily progress
     *
     * @param string $userId
     * @param array $data
     * @return DailyProgress
     */
    public function recordDailyProgress($userId, array $data)
    {
        $today = Carbon::today();
        
        $progress = DailyProgress::updateOrCreate(
            [
                'user_id' => $userId,
                'progress_date' => $today
            ],
            [
                'roadmap_id' => $data['roadmap_id'] ?? null,
                'step_name' => $data['step_name'] ?? null,
                'topic_index' => $data['topic_index'] ?? null,
                'topic_name' => $data['topic_name'] ?? null,
                'topic_completed' => $data['topic_completed'] ?? false,
                'xp_earned' => $data['xp_earned'] ?? 0,
                'time_spent_minutes' => $data['time_spent_minutes'] ?? 0,
                'completed_tasks' => $data['completed_tasks'] ?? [],
                'meta' => $data['meta'] ?? []
            ]
        );
        
        // If topic was completed, check and award badges
        if (!empty($data['topic_completed']) && $data['topic_completed']) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $this->badgeService->checkAndAwardBadges($user);
            }
        }
        
        return $progress;
    }
}

