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
        return [
            'first_name'    => 'nullable|string|max:255',
            'last_name'     => 'nullable|string|max:255',
            'dob'           => 'nullable|date|date_format:Y-m-d',
            'gender'        => 'nullable|in:male,female,other',
            'class_year'    => 'nullable|string|max:255',
            'institute'     => 'nullable|string|max:255',
            'major_subject' => 'nullable|string|max:255',
            'bio'           => 'nullable|string|max:1000',
            'company_size' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
            'website_link' => 'nullable|string|max:255',
            'location' => 'required|string|max:255',
        ];
    }
}
