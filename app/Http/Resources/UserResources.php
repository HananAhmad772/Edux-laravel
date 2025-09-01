<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResources extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'email'      => $this->email,
            'phone' => $this->phone,
            'user_type'  => $this->user_type,
            'status' => $this->status,
        ];

        // Attach related profile based on user_type
        switch ($this->user_type) {
            case 'student':
                $data['student_profile'] = new StudentRegisterResource($this->whenLoaded('studentProfile'));
                break;
            case 'mentor':
                $data['mentor_profile'] = new MentorRegisterResource($this->whenLoaded('mentorProfile'));
                break;
            case 'professional':
                $data['professional_profile'] = new ProfessionalRegisterResource($this->whenLoaded('professionalProfile'));
                break;
            case 'company':
                $data['company_profile'] = new CompanyRegisterResource($this->whenLoaded('companyProfile'));
                break;
        }

        return $data;
    }
}
