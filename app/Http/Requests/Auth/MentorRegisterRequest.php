<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class MentorRegisterRequest extends FormRequest
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
                'qualifications' => 'required|string|max:1000',
                'area_of_expertise' => 'required|string|max:1000',
                'experience_years' => 'required|integer|min:0',
                'bio' => 'nullable|string|max:1000',
                'institute' => 'required|string|max:255',
        ];
    }
}
