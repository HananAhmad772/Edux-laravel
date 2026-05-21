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
            'status' => $this->status,
            'days_since_registration' => $this->days_since_registration,
        ];

        $data['student_profile'] = new StudentRegisterResource($this->whenLoaded('studentProfile'));

        return $data;
    }
}