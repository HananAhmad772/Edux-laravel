<?php
namespace App\Repositories;

use App\Models\StudentProfile;
use App\Models\MentorProfile;
use App\Models\ProfessionalProfile;
use App\Models\CompanyProfile;

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
}
