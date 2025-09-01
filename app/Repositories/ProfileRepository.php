<?php
namespace App\Repositories;

class ProfileRepository
{
    public function createStudent(array $data)
    {
        return \App\Models\StudentProfile::create($data);
    }

    public function createMentor(array $data)
    {
        return \App\Models\MentorProfile::create($data);
    }

    public function createProfessional(array $data)
    {
        return \App\Models\ProfessionalProfile::create($data);
    }

    public function createCompany(array $data)
    {
        return \App\Models\CompanyProfile::create($data);
    }
}
