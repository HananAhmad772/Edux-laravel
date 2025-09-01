<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRegisterRequest extends FormRequest
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
             'company_size' => 'required|string|max:255',
                'industry' => 'required|string|max:255',
                'bio' => 'nullable|string|max:1000',
                'website_link' => 'nullable|string|max:255',
                'location' => 'required|string|max:255',
        ];
    }
}
