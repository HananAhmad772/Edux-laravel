<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\ProfileRepository;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\DB;
use Exception;

class ProfileService
{
    use ApiResponses;

    protected $users;
    protected $profiles;

    public function __construct(UserRepository $users, ProfileRepository $profiles)
    {
        $this->users = $users;
        $this->profiles = $profiles;
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
                $profileFields = ['dob', 'gender', 'class_year', 'institute', 'major_subject', 'bio'];
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
}
