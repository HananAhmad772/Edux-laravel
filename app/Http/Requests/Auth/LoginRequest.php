<?php

namespace App\Http\Requests\Auth;

use App\Traits\ApiResponses;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class LoginRequest extends FormRequest
{
    use ApiResponses;
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
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'user_type' => 'required|string|in:student,job-seeker,company,admin'
        ];
    }

          protected function failedValidation(Validator $validator)
    {
        $firstError = $validator->errors()->first();

        return $this->errorResponse($firstError, 422);
    }
}
