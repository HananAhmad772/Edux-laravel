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
        $baseRules = [
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'phone'      => 'nullable|string|max:20',
        ];

        return array_merge($baseRules, [
            'dob'           => 'nullable|date|date_format:Y-m-d',
            'gender'        => 'nullable|in:male,female,other',
            'class_year'    => 'nullable|string|max:255',
            'institute'     => 'nullable|string|max:255',
            'major_subject' => 'nullable|string|max:255',
            'bio'           => 'nullable|string|max:1000',
            'current_position' => 'nullable|string|max:255',
            'specialization_field' => 'nullable|string|max:255',
            'preferred_technologies' => 'nullable|array',
            'preferred_technologies.*' => 'string|max:255',
            'current_skill_level' => 'nullable|string|max:255',
            'main_goal' => 'nullable|string|max:255',
            'time_per_week' => 'nullable|string|max:255',
        ]);
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
