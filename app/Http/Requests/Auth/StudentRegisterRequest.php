<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StudentRegisterRequest extends FormRequest
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
            'dob'           => 'required|date|date_format:Y-m-d',
            'gender'        => 'required|in:male,female,other',
            'class_year'    => 'required|string|max:255',
            'institute'     => 'required|string|max:255',
            'major_subject' => 'required|string|max:255',
            'bio'           => 'nullable|string|max:1000',
        ];
    }
}
