<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userType = $this->input('user_type') ?? auth()->user()->user_type ?? 'student';
        
        $baseRules = [
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'phone'      => 'nullable|string|max:20',
        ];

        switch ($userType) {
            case 'student':
                return array_merge($baseRules, [
                    'dob'           => 'nullable|date|date_format:Y-m-d',
                    'gender'        => 'nullable|in:male,female,other',
                    'class_year'    => 'nullable|string|max:255',
                    'institute'     => 'nullable|string|max:255',
                    'major_subject' => 'nullable|string|max:255',
                    'bio'           => 'nullable|string|max:1000',
                ]);

            case 'mentor':
                return array_merge($baseRules, [
                    'qualifications'    => 'nullable|string|max:1000',
                    'area_of_expertise' => 'nullable|array',
                    'area_of_expertise.*' => 'string|max:255',
                    'experience_years'  => 'nullable|integer|min:0|max:50',
                    'institute'         => 'nullable|string|max:255',
                    'bio'               => 'nullable|string|max:1000',
                ]);

            case 'professional':
                return array_merge($baseRules, [
                    'executive_summary' => 'nullable|string|max:1000',
                    'skills'            => 'nullable|array',
                    'skills.*'          => 'string|max:255',
                    'current_position'  => 'nullable|string|max:255',
                    'year_of_experience' => 'nullable|string|max:50',
                    'bio'               => 'nullable|string|max:1000',
                ]);

            case 'company':
                return array_merge($baseRules, [
                    'company_size'  => 'nullable|string|max:255',
                    'industry'      => 'nullable|string|max:255',
                    'bio'           => 'nullable|string|max:1000',
                    'website_link'  => 'nullable|url|max:255',
                    'location'      => 'nullable|string|max:255',
                ]);

            default:
                return $baseRules;
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'area_of_expertise.*.string' => 'Each area of expertise must be a string.',
            'area_of_expertise.*.max' => 'Each area of expertise must not exceed 255 characters.',
            'skills.*.string' => 'Each skill must be a string.',
            'skills.*.max' => 'Each skill must not exceed 255 characters.',
            'website_link.url' => 'The website link must be a valid URL.',
        ];
    }
}
