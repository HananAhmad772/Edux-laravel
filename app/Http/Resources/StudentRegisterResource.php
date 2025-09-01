<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentRegisterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
            'dob'          => $this->dob,
            'gender'       => $this->gender,
            'class_year'   => $this->class_year,
            'institute'    => $this->institute,
            'major_subject'=> $this->major_subject,
            'bio'          => $this->bio,
        ];
    }
}
