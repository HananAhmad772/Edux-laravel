<?php

namespace App\Http\Requests\Auth;

use App\Traits\ApiResponses;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class ForgotPasswordRequest extends FormRequest
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
            'email' => 'nullable|email|required_without:phone|exists:users,email',
            'phone' => 'nullable|string|min:10|max:15|required_without:email|exists:users,phone',
        ];
    }

          protected function failedValidation(Validator $validator)
    {
        $firstError = $validator->errors()->first();

        return $this->errorResponse($firstError, 422);
    }
}
