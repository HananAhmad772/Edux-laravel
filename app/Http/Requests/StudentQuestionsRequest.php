<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentQuestionsRequest extends FormRequest
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
            'major_subject' => 'required|string|max:255',
            'current_position' => 'required|string|max:255',
            'specialization_field' => 'required|string|max:255',
            'preferred_technologies' => 'required',
            'current_skill_level' => 'required|string|max:255',
            'main_goal' => 'required|string|max:255',
            'time_per_week' => 'required|string|max:255',
        ];
    }
    
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'preferred_technologies' => $this->formatPreferredTechnologies($this->preferred_technologies),
        ]);
        
        parent::prepareForValidation();
    }
    
    /**
     * Format preferred technologies to string format.
     */
    private function formatPreferredTechnologies($technologies)
    {
        if (is_array($technologies)) {
            return implode(',', $technologies);
        }
        
        return $technologies;
    }
}