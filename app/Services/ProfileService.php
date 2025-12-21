<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\ProfileRepository;
use App\Services\AIQuizService;
use App\Services\AIRoadmapService;
use App\Services\AIDailyChallengeService;
use App\Services\BadgeService;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProfileService
{
    use ApiResponses;
    
    protected $users;
    protected $profiles;
    protected $aiQuizService;
    protected $aiRoadmapService;
    protected $aiDailyChallengeService;
    protected $badgeService;

    public function __construct(
        UserRepository $users,
        ProfileRepository $profiles,
        AIQuizService $aiQuizService,
        AIRoadmapService $aiRoadmapService,
        AIDailyChallengeService $aiDailyChallengeService
    ) {
        $this->users = $users;
        $this->profiles = $profiles;
        $this->aiQuizService = $aiQuizService;
        $this->aiRoadmapService = $aiRoadmapService;
        $this->aiDailyChallengeService = $aiDailyChallengeService;
        $this->badgeService = new BadgeService();
    }

    /**
     * Update user profile based on user type
     */
    public function updateProfile($userId, array $data)
    {
        return DB::transaction(function () use ($userId, $data) {
            $user = $this->users->findById($userId);
            
            if (!$user) {
                return [
                    'status' => false,
                    'message' => 'User not found',
                    'code' => 404
                ];
            }

            $userType = $user->user_type;

            // Update basic user information
            $userData = array_filter([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
            ], function($value) {
                return $value !== null;
            });

            if (!empty($userData)) {
                $user->update($userData);
            }

            // Update profile based on user type
            $profileData = $this->filterProfileData($data, $userType);
            
            if (!empty($profileData)) {
                $profileUpdated = $this->updateProfileByType($userId, $userType, $profileData);
                
                if ($profileUpdated === null) {
                    return [
                        'status' => false,
                        'message' => 'Profile not found for this user type',
                        'code' => 404
                    ];
                }
            }

            // Load updated user with profile
            $updatedUser = $this->users->findUserById($userId, [$this->getProfileRelation($userType)]);

            return [
                'status' => true,
                'message' => 'Profile updated successfully',
                'data' => $updatedUser,
                'code' => 200
            ];
        });
    }

    /**
     * Filter profile data based on user type
     */
    private function filterProfileData(array $data, string $userType): array
    {
        $profileFields = [];

        switch ($userType) {
            case 'student':
                $profileFields = ['dob', 'gender', 'class_year', 'institute', 'major_subject', 'bio', 'current_position', 'specialization_field', 'preferred_technologies', 'current_skill_level', 'main_goal', 'time_per_week'];
                break;
            case 'mentor':
                $profileFields = ['qualifications', 'area_of_expertise', 'experience_years', 'institute', 'bio'];
                break;
            case 'professional':
                $profileFields = ['executive_summary', 'skills', 'current_position', 'year_of_experience', 'bio'];
                break;
            case 'company':
                $profileFields = ['company_size', 'industry', 'bio', 'website_link', 'location'];
                break;
        }

        return array_filter($data, function($key) use ($profileFields) {
            return in_array($key, $profileFields);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Update profile based on user type
     */
    private function updateProfileByType($userId, string $userType, array $data)
    {
        switch ($userType) {
            case 'student':
                return $this->profiles->updateStudent($userId, $data);
            case 'mentor':
                return $this->profiles->updateMentor($userId, $data);
            case 'professional':
                return $this->profiles->updateProfessional($userId, $data);
            case 'company':
                return $this->profiles->updateCompany($userId, $data);
            default:
                return null;
        }
    }

    /**
     * Get the appropriate profile relation based on user type
     */
    private function getProfileRelation(string $userType): string
    {
        switch ($userType) {
            case 'student':
                return 'studentProfile';
            case 'mentor':
                return 'mentorProfile';
            case 'professional':
                return 'professionalProfile';
            case 'company':
                return 'companyProfile';
            default:
                return '';
        }
    }

    /**
     * Get user profile with appropriate relation
     */
    public function getProfile($userId)
    {
        $user = $this->users->findById($userId);
        
        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found',
                'code' => 404
            ];
        }

        $userType = $user->user_type;
        $relation = $this->getProfileRelation($userType);
        
        $userWithProfile = $this->users->findUserById($userId, [$relation]);

        return [
            'status' => true,
            'message' => 'Profile retrieved successfully',
            'data' => $userWithProfile,
            'code' => 200
        ];
    }
    
    /**
     * Update student questions data
     */
    public function updateStudentQuestions($userId, array $data)
    {
        return DB::transaction(function () use ($userId, $data) {
            $profile = $this->profiles->getStudentProfile($userId);
            
            // If profile doesn't exist, create it
            if (!$profile) {
                $profile = $this->profiles->createStudent([
                    'user_id' => $userId
                ]);
                
                // If still no profile, return error
                if (!$profile) {
                    return [
                        'status' => false,
                        'message' => 'Failed to create student profile',
                        'code' => 500
                    ];
                }
            }
            
            $profile->update($data);

            // Generate AI quiz questions based on updated profile
            $quizResult = $this->aiQuizService->generateQuizQuestions($profile);
            
            if ($quizResult['success']) {
                // Save the generated quiz
                $quiz = $this->aiQuizService->saveQuiz($userId, $quizResult['data']);
                
                return [
                    'status' => true,
                    'message' => 'Student questions updated and quiz generated successfully',
                    'data' => [
                        'profile' => $profile,
                        'quiz' => $quiz
                    ],
                    'code' => 200
                ];
            } else {
                // Return profile update success but quiz generation failure
                return [
                    'status' => true,
                    'message' => 'Student questions updated but failed to generate quiz: ' . $quizResult['message'],
                    'data' => [
                        'profile' => $profile,
                        'quiz_error' => $quizResult['message']
                    ],
                    'code' => 200
                ];
            }
        });
    }
    
    /**
     * Store student quiz data
     */
    public function storeStudentQuiz($userId, array $data)
    {
        return DB::transaction(function () use ($userId, $data) {
            $quizData = array_merge($data, ['student_id' => $userId]);
            $quiz = $this->profiles->createStudentQuiz($quizData);

            return [
                'status' => true,
                'message' => 'Student quiz stored successfully',
                'data' => $quiz,
                'code' => 201
            ];
        });
    }
    
    /**
     * Get student quizzes
     */
    public function getStudentQuizzes($userId)
    {
        $quizzes = $this->profiles->getStudentQuizzes($userId);
        
        // The StudentQuiz model already casts questions and answers to arrays,
        // so we don't need to manually decode them
        
        return [
            'status' => true,
            'message' => 'Student quizzes retrieved successfully',
            'data' => $quizzes,
            'code' => 200
        ];
    }
    
    /**
     * Generate AI quiz for a student
     */
    public function generateAIQuiz($userId)
    {
        $profile = $this->profiles->getStudentProfile($userId);
        
        // If profile doesn't exist, create it
        if (!$profile) {
            $profile = $this->profiles->createStudent([
                'user_id' => $userId
            ]);
            
            // If still no profile, return error
            if (!$profile) {
                return [
                    'status' => false,
                    'message' => 'Failed to create student profile',
                    'code' => 500
                ];
            }
        }
        
        $quizResult = $this->aiQuizService->generateQuizQuestions($profile);
        
        if ($quizResult['success']) {
            // Save the generated quiz
            $quiz = $this->aiQuizService->saveQuiz($userId, $quizResult['data']);
            
            return [
                'status' => true,
                'message' => 'AI quiz generated successfully',
                'data' => $quiz,
                'code' => 200
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Failed to generate AI quiz: ' . $quizResult['message'],
                'code' => 500
            ];
        }
    }
    
    /**
     * Generate personalized learning roadmap for a student
     */
    public function generatePersonalizedRoadmap($userId, $forceRegenerate = false)
    {
        \Illuminate\Support\Facades\Log::info('Starting personalized roadmap generation', ['user_id' => $userId]);
        
        $profile = $this->profiles->getStudentProfile($userId);
        
        // If profile doesn't exist, create it
        if (!$profile) {
            $profile = $this->profiles->createStudent([
                'user_id' => $userId
            ]);
            
            // If still no profile, return error
            if (!$profile) {
                \Illuminate\Support\Facades\Log::warning('Failed to create student profile for roadmap generation', ['user_id' => $userId]);
                return [
                    'status' => false,
                    'message' => 'Failed to create student profile',
                    'code' => 500
                ];
            }
        }
        
        // Check if user has a recent roadmap (within 24 hours) unless forced
        if (!$forceRegenerate) {
            $latestRoadmap = $this->profiles->getLatestStudentRoadmap($userId);
            if ($latestRoadmap && $latestRoadmap->created_at) {
                $hoursSinceCreation = now()->diffInHours($latestRoadmap->created_at);
                // Block regeneration if the roadmap is less than 24 hours old
                if ($hoursSinceCreation < 24) {
                    \Illuminate\Support\Facades\Log::info('Roadmap regeneration blocked - within 24 hours of creation', [
                        'user_id' => $userId,
                        'hours_since_creation' => $hoursSinceCreation,
                        'roadmap_id' => $latestRoadmap->id
                    ]);
                    $hoursRemaining = 24 - $hoursSinceCreation;
                    return [
                        'status' => false,
                        'message' => 'You cannot regenerate a roadmap within 24 hours of creation. Please wait ' . $hoursRemaining . ' more hours.',
                        'code' => 429,
                        'hours_remaining' => $hoursRemaining
                    ];
                }
            }
        }
        
        // Get student quizzes
        $quizzes = $this->profiles->getStudentQuizzes($userId);
        
        // Convert quizzes to array format for the AI service
        $quizData = $quizzes->toArray();
        
        \Illuminate\Support\Facades\Log::info('Student profile and quizzes retrieved', [
            'user_id' => $userId,
            'quiz_count' => count($quizData)
        ]);
        
        // Generate roadmap using AI
        $roadmapResult = $this->aiRoadmapService->generateLearningRoadmap($profile, $quizData);
        
       if ($roadmapResult['success']) {

    \Log::info('RAW ROADMAP GENERATED BEFORE SAVING =====================', [
        'user_id'    => $userId,
        'length'     => strlen($roadmapResult['data']),
    ]);

    \Log::info("RAW ROADMAP CONTENT:\n" . $roadmapResult['data']);

    // Use the AIRoadmapService saveRoadmap method to properly save both roadmap_content and roadmap_json
    $roadmap = $this->aiRoadmapService->saveRoadmap($userId, $roadmapResult['data']);

    if (!$roadmap) {
        return [
            'status' => false,
            'message' => 'Failed to save roadmap to database',
            'code' => 500
        ];
    }

            
            return [
                'status' => true,
                'message' => 'Personalized learning roadmap generated successfully',
                'data' => $roadmap,
                'code' => 200
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Failed to generate personalized learning roadmap: ' . $roadmapResult['message'],
                'code' => 500
            ];
        }
    }
    
    /**
     * Get all roadmaps for a student
     */
    public function getStudentRoadmaps($userId)
    {
        $roadmaps = $this->profiles->getStudentRoadmaps($userId);
        
        return [
            'status' => true,
            'message' => 'Student roadmaps retrieved successfully',
            'data' => $roadmaps,
            'code' => 200
        ];
    }
    
    /**
     * Get the latest roadmap for a student
     */
    public function getLatestStudentRoadmap($userId)
    {
        $roadmap = $this->profiles->getLatestStudentRoadmap($userId);
        
        if (!$roadmap) {
            return [
                'status' => false,
                'message' => 'No roadmap found for this student',
                'code' => 404
            ];
        }
        
        return [
            'status' => true,
            'message' => 'Latest student roadmap retrieved successfully',
            'data' => $roadmap,
            'code' => 200
        ];
    }
    
    /**
     * Check if a roadmap is complete based on user progress
     */
    private function isRoadmapComplete($roadmap, $userProgress)
    {
        try {
            // If no user progress, roadmap is not complete
            if (!$userProgress) {
                return false;
            }
            
            // Parse roadmap content to extract steps and topics
            $parsedRoadmap = $this->parseRoadmapContent($roadmap->roadmap_content, $roadmap->roadmap_json);
            
            // If no steps in roadmap, consider it incomplete
            if (empty($parsedRoadmap)) {
                return false;
            }
            
            // Get all steps
            $steps = array_values($parsedRoadmap);
            $lastStep = end($steps);
            
            // Check if user has reached the last step and last topic
            if ($userProgress->current_step === $lastStep['name'] || $userProgress->current_step === $lastStep['duration']) {
                $totalTopicsInLastStep = count($lastStep['topics'] ?? []);
                if ($userProgress->current_topic_index >= $totalTopicsInLastStep) {
                    return true; // User has completed all topics in the last step
                }
            }
            
            return false; // User has not completed the roadmap
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error checking roadmap completion: ' . $e->getMessage());
            return false; // In case of error, assume roadmap is not complete
        }
    }
    
    /**
     * Parse roadmap content into structured data
     *
     * @param string $roadmapContent
     * @param mixed $roadmapJson
     * @return array
     */
    private function parseRoadmapContent($roadmapContent, $roadmapJson = null)
    {
        // If roadmap_json exists and is valid, use it
        if ($roadmapJson) {
            if (is_string($roadmapJson)) {
                $decoded = json_decode($roadmapJson, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } elseif (is_array($roadmapJson)) {
                return $roadmapJson;
            }
        }
        
        // Otherwise, parse the content
        return $this->parseRoadmapContentFromString($roadmapContent);
    }
    
    /**
     * Parse roadmap content from string
     *
     * @param string $roadmapContent
     * @return array
     */
    private function parseRoadmapContentFromString($roadmapContent)
    {
        $steps = [];
        $lines = explode("\n", $roadmapContent);
        $currentStep = null;
        $currentSection = null;
        $stepPattern = '/^\*\*Week (\d+)–(\d+): (.+)\*\*$/';
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            // Check for step headings
            if (preg_match($stepPattern, $trimmedLine, $matches)) {
                $stepName = "Week {$matches[1]}–{$matches[2]}";
                $stepTitle = $matches[3];
                
                $currentStep = [
                    'name' => $stepName,
                    'title' => $stepTitle,
                    'topics' => [],
                    'tools' => [],
                    'skills' => [],
                    'tasks' => []
                ];
                
                $steps[$stepName] = $currentStep;
                $currentSection = null;
            } else if ($currentStep) {
                // Check for section headings
                if (strpos($trimmedLine, 'Topics to study') !== false) {
                    $currentSection = 'topics';
                } else if (strpos($trimmedLine, 'Tools to use') !== false) {
                    $currentSection = 'tools';
                } else if (strpos($trimmedLine, 'Skills learned') !== false) {
                    $currentSection = 'skills';
                } else if (strpos($trimmedLine, 'Mini practice tasks or micro-projects') !== false) {
                    $currentSection = 'tasks';
                } else if ($currentSection && preg_match('/^\s*\*\s*(.+)$/', $trimmedLine, $matches)) {
                    // Add item to current section
                    $steps[$currentStep['name']][$currentSection][] = trim($matches[1]);
                }
            }
        }
        
        return $steps;
    }
    
    /**
     * Get current roadmap with topics organized by days
     *
     * @param string $userId
     * @return array
     */
    public function getCurrentRoadmapWithTopics($userId)
    {
        try {
            // Get user progress
            $userProgress = \App\Models\UserProgress::where('user_id', $userId)->first();
            
            // Get latest roadmap
            $roadmap = \App\Models\StudentRoadmap::where('student_id', $userId)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$roadmap) {
                return [
                    'status' => false,
                    'message' => 'No roadmap found for this student',
                    'code' => 404
                ];
            }
            
            // Parse roadmap JSON
            $roadmapJson = $roadmap->roadmap_json ?? [];
            
            // If roadmap_json is a string, decode it
            if (is_string($roadmapJson)) {
                $roadmapJson = json_decode($roadmapJson, true);
            }
            
            $steps = $roadmapJson['steps'] ?? [];
            
            if (empty($steps)) {
                return [
                    'status' => false,
                    'message' => 'Invalid roadmap structure',
                    'code' => 500
                ];
            }
            
            // Organize roadmap by days
            $dayWiseRoadmap = $this->organizeRoadmapByDays($steps, $userProgress);
            
            // Get today, yesterday, and tomorrow topics
            $todayTopic = null;
            $yesterdayTopic = null;
            $tomorrowTopic = null;
            
            if ($userProgress) {
                $currentDayNumber = $this->getDayNumberFromProgress($userProgress, $dayWiseRoadmap);
                
                // Get today's topic
                $todayKey = "day_" . $currentDayNumber;
                if (isset($dayWiseRoadmap[$todayKey])) {
                    $todayTopic = $dayWiseRoadmap[$todayKey];
                }
                
                // Get yesterday's topic (skip Sundays - 6-day weeks)
                $yesterdayDayNumber = $this->calculatePreviousWorkingDay($currentDayNumber);
                $yesterdayKey = "day_" . $yesterdayDayNumber;
                if (isset($dayWiseRoadmap[$yesterdayKey])) {
                    $yesterdayTopic = $dayWiseRoadmap[$yesterdayKey];
                }
                
                // Get tomorrow's topic (skip Sundays - 6-day weeks)
                $tomorrowDayNumber = $this->calculateNextWorkingDay($currentDayNumber);
                $tomorrowKey = "day_" . $tomorrowDayNumber;
                if (isset($dayWiseRoadmap[$tomorrowKey])) {
                    $tomorrowTopic = $dayWiseRoadmap[$tomorrowKey];
                }
            } else {
                // If no progress, default to first day
                if (isset($dayWiseRoadmap['day_1'])) {
                    $todayTopic = $dayWiseRoadmap['day_1'];
                    
                    // Tomorrow would be day 2 (or day 3 if Sunday is skipped)
                    $tomorrowDayNumber = $this->calculateNextWorkingDay(1);
                    $tomorrowKey = "day_" . $tomorrowDayNumber;
                    if (isset($dayWiseRoadmap[$tomorrowKey])) {
                        $tomorrowTopic = $dayWiseRoadmap[$tomorrowKey];
                    }
                }
            }
            
            return [
                'status' => true,
                'message' => 'Current roadmap with topics retrieved successfully',
                'data' => [
                    'roadmap' => $roadmap,
                    'day_wise_roadmap' => $dayWiseRoadmap,
                    'today' => $todayTopic,
                    'yesterday' => $yesterdayTopic,
                    'tomorrow' => $tomorrowTopic,
                    'current_progress' => $userProgress,
                    'student' => \App\Models\User::with('studentProfile')->find($userId)
                ],
                'code' => 200
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error getting current roadmap with topics: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Failed to retrieve current roadmap with topics',
                'code' => 500
            ];
        }
    }
    
    /**
     * Organize roadmap steps into a day-wise structure
     *
     * @param array $steps
     * @param \App\Models\UserProgress|null $userProgress
     * @return array
     */
    private function organizeRoadmapByDays($steps, $userProgress)
    {
        $dayWiseRoadmap = [];
        $dayCounter = 1;
        
        foreach ($steps as $stepIndex => $step) {
            $stepDuration = $step['duration'] ?? '';
            $stepTitle = $step['title'] ?? '';
            $topics = $step['topics'] ?? [];
            
            // Each topic becomes a separate day
            foreach ($topics as $topicIndex => $topic) {
                $dayKey = "day_" . $dayCounter;
                
                $dayWiseRoadmap[$dayKey] = [
                    'day_number' => $dayCounter,
                    'topic' => $topic,
                    'step' => $stepDuration,
                    'step_title' => $stepTitle,
                    'topic_index' => $topicIndex + 1,
                    'week_number' => $this->getWeekNumber($dayCounter)
                ];
                
                $dayCounter++;
            }
        }
        
        return $dayWiseRoadmap;
    }
    
    /**
     * Calculate the week number based on day number (6-day weeks, skipping Sundays)
     *
     * @param int $dayNumber
     * @return int
     */
    private function getWeekNumber($dayNumber)
    {
        // Since we're skipping Sundays, each week has 6 days
        return ceil($dayNumber / 6);
    }
    
    /**
     * Calculate the previous working day (skipping Sundays)
     *
     * @param int $currentDay
     * @return int
     */
    private function calculatePreviousWorkingDay($currentDay)
    {
        $previousDay = $currentDay - 1;
        
        // If the previous day would be a Sunday (divisible by 6 with no remainder when considering 6-day weeks)
        // We need to skip it
        if ($previousDay % 6 === 0 && $previousDay !== 0) {
            $previousDay -= 1; // Skip Sunday
        }
        
        return max(1, $previousDay); // Ensure we don't go below day 1
    }
    
    /**
     * Calculate the next working day (skipping Sundays)
     *
     * @param int $currentDay
     * @return int
     */
    private function calculateNextWorkingDay($currentDay)
    {
        $nextDay = $currentDay + 1;
        
        // If the next day would be a Sunday (divisible by 6 with no remainder when considering 6-day weeks)
        // We need to skip it
        if ($nextDay % 6 === 0 && $nextDay !== 0) {
            $nextDay += 1; // Skip Sunday
        }
        
        return $nextDay;
    }
    
    /**
     * Get day number from user progress
     *
     * @param \App\Models\UserProgress $userProgress
     * @param array $dayWiseRoadmap
     * @return int
     */
    private function getDayNumberFromProgress($userProgress, $dayWiseRoadmap)
    {
        $currentStep = $userProgress->current_step;
        $currentTopicIndex = $userProgress->current_topic_index;
        
        // Find the day number that matches the current progress
        foreach ($dayWiseRoadmap as $dayKey => $dayData) {
            if ($dayData['step'] === $currentStep && $dayData['topic_index'] === $currentTopicIndex) {
                return $dayData['day_number'];
            }
        }
        
        // Default to day 1 if not found
        return 1;
    }

    /**
     * Advance user progress to the next topic
     *
     * @param string $userId
     * @return array
     */
    public function advanceUserProgress($userId)
    {
        try {
            // Get user progress
            $userProgress = \App\Models\UserProgress::where('user_id', $userId)->first();
            
            if (!$userProgress) {
                return [
                    'status' => false,
                    'message' => 'User progress not found',
                    'code' => 404
                ];
            }
            
            // Get latest roadmap
            $roadmap = \App\Models\StudentRoadmap::where('student_id', $userId)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$roadmap) {
                return [
                    'status' => false,
                    'message' => 'No roadmap found for this student',
                    'code' => 404
                ];
            }
            
            // Parse roadmap JSON
            $roadmapJson = $roadmap->roadmap_json ?? [];
            $steps = $roadmapJson['steps'] ?? [];
            
            if (empty($steps)) {
                return [
                    'status' => false,
                    'message' => 'Invalid roadmap structure',
                    'code' => 500
                ];
            }
            
            // Find current step
            $currentStep = null;
            $currentStepIndex = -1;
            foreach ($steps as $index => $step) {
                if (($step['duration'] ?? '') === $userProgress->current_step) {
                    $currentStep = $step;
                    $currentStepIndex = $index;
                    break;
                }
            }
            
            if (!$currentStep) {
                return [
                    'status' => false,
                    'message' => 'Current step not found in roadmap',
                    'code' => 500
                ];
            }
            
            $topicsList = $currentStep['topics'] ?? [];
            $totalTopicsInStep = count($topicsList);
            
            // Advance to next topic
            $newTopicIndex = $userProgress->current_topic_index + 1;
            
            // If we've completed all topics in current step, move to next step
            if ($newTopicIndex > $totalTopicsInStep && $currentStepIndex < (count($steps) - 1)) {
                $nextStep = $steps[$currentStepIndex + 1];
                $userProgress->current_step = $nextStep['duration'] ?? '';
                $userProgress->current_topic_index = 1;
            } else if ($newTopicIndex <= $totalTopicsInStep) {
                // Stay in current step, advance topic index
                $userProgress->current_topic_index = $newTopicIndex;
            }
            // If we're at the last topic of the last step, we stay there
            
            $userProgress->save();
            
            // Get user object to check badges
            $user = \App\Models\User::find($userId);
            if ($user) {
                // Check and award badges based on updated progress
                $this->badgeService->checkAndAwardBadges($user);
            }
            
            return [
                'status' => true,
                'message' => 'User progress advanced successfully',
                'data' => $userProgress,
                'code' => 200
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error advancing user progress: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Failed to advance user progress',
                'code' => 500
            ];
        }
    }
    
    /**
     * Chat with AI using topic restrictions
     *
     * @param string $userId
     * @param array $messages
     * @return array
     */
    public function chatWithAI($userId, array $messages)
    {
        try {
            // Get the AI chatbot mediator service
            $aiChatbotMediatorService = new \App\Services\AIChatbotMediatorService(new \App\Services\AIChatbotService());
            
            // Get latest roadmap
            $roadmap = \App\Models\StudentRoadmap::where('student_id', $userId)
                ->orderBy('created_at', 'desc')
                ->first();
            
            $roadmapId = $roadmap ? $roadmap->id : null;
            
            // Generate response from AI with topic restrictions
            $result = $aiChatbotMediatorService->generateTopicRestrictedResponse($messages, $userId, $roadmapId);
            
            return [
                'status' => true,
                'message' => 'AI response generated successfully',
                'data' => $result,
                'code' => 200
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error chatting with AI: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Failed to generate AI response',
                'code' => 500
            ];
        }
    }
      /**
     * Generate daily challenge for the student
     *
     * @param string $userId
     * @return array
     */

    /**
     * Generate daily challenge for the student
     *
     * @param string $userId
     * @return array
     */
    public function generateDailyChallenge($userId)
    {
        try {
            $result = $this->aiDailyChallengeService->generateDailyChallenge($userId);
            
            if (!$result['success']) {
                return [
                    'status' => false,
                    'message' => $result['message'],
                    'code' => 500
                ];
            }
            
            return [
                'status' => true,
                'message' => $result['message'],
                'data' => $result['data'],
                'code' => 200
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating daily challenge: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Failed to generate daily challenge',
                'code' => 500
            ];
        }
    }
    
    /**
     * Evaluate student's submission for a daily challenge
     *
     * @param string $challengeId
     * @param string $submission
     * @return array
     */
    public function evaluateDailyChallengeSubmission($challengeId, $submission)
    {
        try {
            $result = $this->aiDailyChallengeService->evaluateSubmission($challengeId, $submission);
            
            if (!$result['success']) {
                return [
                    'status' => false,
                    'message' => $result['message'],
                    'code' => 500
                ];
            }
            
            return [
                'status' => true,
                'message' => $result['message'],
                'data' => $result['data'],
                'code' => 200
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error evaluating daily challenge submission: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Failed to evaluate daily challenge submission',
                'code' => 500
            ];
        }
    }

}