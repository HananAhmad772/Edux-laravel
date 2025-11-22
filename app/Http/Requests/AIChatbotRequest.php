<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AIChatbotRequest extends FormRequest
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
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|string|in:user,assistant,system',
            'messages.*.content' => 'required|string'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'messages.required' => 'Messages are required',
            'messages.array' => 'Messages must be an array',
            'messages.min' => 'At least one message is required',
            'messages.*.role.required' => 'Each message must have a role',
            'messages.*.role.in' => 'Message role must be user, assistant, or system',
            'messages.*.content.required' => 'Each message must have content',
            'messages.*.content.string' => 'Message content must be a string'
        ];
    }
}