<?php
namespace App\Repositories;

use App\Models\StudentProfile;
use App\Models\MentorProfile;
use App\Models\ProfessionalProfile;
use App\Models\CompanyProfile;
use App\Models\StudentQuiz;
use App\Models\StudentRoadmap;
use App\Models\UserProgress;

class ProfileRepository
{
    public function createStudent(array $data)
    {
        return StudentProfile::create($data);
    }

    public function createMentor(array $data)
    {
        return MentorProfile::create($data);
    }

    public function createProfessional(array $data)
    {
        return ProfessionalProfile::create($data);
    }

    public function createCompany(array $data)
    {
        return CompanyProfile::create($data);
    }

    public function updateStudent($userId, array $data)
    {
        $profile = StudentProfile::where('user_id', $userId)->first();
        
        if (!$profile) {
            return null;
        }

        return $profile->update($data);
    }

    public function updateMentor($userId, array $data)
    {
        $profile = MentorProfile::where('user_id', $userId)->first();
        
        if (!$profile) {
            return null;
        }

        return $profile->update($data);
    }

    public function updateProfessional($userId, array $data)
    {
        $profile = ProfessionalProfile::where('user_id', $userId)->first();
        
        if (!$profile) {
            return null;
        }

        return $profile->update($data);
    }

    public function updateCompany($userId, array $data)
    {
        $profile = CompanyProfile::where('user_id', $userId)->first();
        
        if (!$profile) {
            return null;
        }

        return $profile->update($data);
    }

    public function getStudentProfile($userId)
    {
        return StudentProfile::where('user_id', $userId)->first();
    }

    public function getMentorProfile($userId)
    {
        return MentorProfile::where('user_id', $userId)->first();
    }

    public function getProfessionalProfile($userId)
    {
        return ProfessionalProfile::where('user_id', $userId)->first();
    }

    public function getCompanyProfile($userId)
    {
        return CompanyProfile::where('user_id', $userId)->first();
    }
    
    public function createStudentQuiz(array $data)
    {
        return StudentQuiz::create($data);
    }
    
    public function getStudentQuizzes($studentId)
    {
        return StudentQuiz::where('student_id', $studentId)->get();
    }
    
    public function createStudentRoadmap(array $data)
    {
        return StudentRoadmap::create($data);
    }
    
    public function getStudentRoadmaps($studentId)
    {
        return StudentRoadmap::where('student_id', $studentId)->get();
    }
    
    public function getLatestStudentRoadmap($studentId)
    {
        return StudentRoadmap::where('student_id', $studentId)->latest()->first();
    }
    
    // User Progress methods
    public function createUserProgress(array $data)
    {
        return UserProgress::create($data);
    }
    
    public function getUserProgress($userId)
    {
        return UserProgress::where('user_id', $userId)->first();
    }
    
    public function updateUserProgress($userId, array $data)
    {
        $progress = UserProgress::where('user_id', $userId)->first();
        
        if (!$progress) {
            return null;
        }

        return $progress->update($data);
    }
    
    public function getOrCreateUserProgress($userId, $roadmapId = null)
    {
        $progress = UserProgress::where('user_id', $userId)->first();
        
        if (!$progress) {
            // Get the roadmap to determine the first step
            $currentStep = null;
            if ($roadmapId) {
                $roadmap = \App\Models\StudentRoadmap::find($roadmapId);
                if ($roadmap) {
                    // Parse the roadmap to get the first step
                    $lines = explode("\n", $roadmap->roadmap_content);
                    foreach ($lines as $line) {
                        $trimmedLine = trim($line);
                        if (preg_match('/^\*\*Week (\d+)–(\d+): (.+)\*\*$/', $trimmedLine, $matches)) {
                            $currentStep = "Week {$matches[1]}–{$matches[2]}";
                            break;
                        }
                    }
                }
            }
            
            $progress = UserProgress::create([
                'user_id' => $userId,
                'roadmap_id' => $roadmapId,
                'current_step' => $currentStep,
                'current_topic_index' => 1,
                'last_active_at' => now()
            ]);
        }
        
        return $progress;
    }
}