<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\ProfileRepository;
use App\Services\AIQuizService;
use App\Services\AIRoadmapService;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\DB;
use Exception;

class ProfileService
{
    use ApiResponses;

    protected $users;
    protected $profiles;
    protected $aiQuizService;
    protected $aiRoadmapService;

    public function __construct(UserRepository $users, ProfileRepository $profiles, AIQuizService $aiQuizService, AIRoadmapService $aiRoadmapService)
    {
        $this->users = $users;
        $this->profiles = $profiles;
        $this->aiQuizService = $aiQuizService;
        $this->aiRoadmapService = $aiRoadmapService;
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
            
            if (!$profile) {
                return [
                    'status' => false,
                    'message' => 'Student profile not found',
                    'code' => 404
                ];
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
        
        if (!$profile) {
            return [
                'status' => false,
                'message' => 'Student profile not found',
                'code' => 404
            ];
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
    public function generatePersonalizedRoadmap($userId)
    {
        \Illuminate\Support\Facades\Log::info('Starting personalized roadmap generation', ['user_id' => $userId]);
        
        $profile = $this->profiles->getStudentProfile($userId);
        
        if (!$profile) {
            \Illuminate\Support\Facades\Log::warning('Student profile not found for roadmap generation', ['user_id' => $userId]);
            return [
                'status' => false,
                'message' => 'Student profile not found',
                'code' => 404
            ];
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
            // Save the generated roadmap
            $roadmap = $this->profiles->createStudentRoadmap([
                'student_id' => $userId,
                'roadmap_content' => $roadmapResult['data']
            ]);
            
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
}