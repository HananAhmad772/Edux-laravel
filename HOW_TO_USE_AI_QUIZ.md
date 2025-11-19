# How to Use the AI Quiz Functionality

## Overview
The AI quiz functionality generates personalized quiz questions for students based on their profile information using the Hugging Face API.

## Prerequisites
1. Laravel application running
2. Valid Hugging Face API key in `.env` file
3. Student user account with completed profile

## Testing the AI Quiz Functionality

### Method 1: Using the Laravel API Endpoint (Recommended)

1. **Start the Laravel application**:
   ```bash
   php artisan serve
   ```

2. **Register or log in as a student**:
   - Use the `/api/auth/login` endpoint to get an access token
   - Make sure the student profile is complete with all required fields

3. **Call the AI quiz generation endpoint**:
   ```bash
   curl -X POST http://localhost:8000/api/auth/student/generate-quiz \
     -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
     -H "Content-Type: application/json"
   ```

### Method 2: Direct Hugging Face API Testing

1. **Test the API key**:
   ```bash
   python test_huggingface_api.py
   ```

2. **Test quiz generation**:
   ```bash
   python test_ai_prompt.py
   ```

## Required Student Profile Fields
The AI quiz generation requires the following student profile fields to be completed:
- `major_subject`
- `current_position`
- `specialization_field`
- `preferred_technologies`
- `current_skill_level`
- `main_goal`
- `time_per_week`

## How It Works

1. **Profile Data Collection**: The system collects student profile information
2. **Prompt Generation**: A prompt is created based on the student's profile
3. **AI Processing**: The prompt is sent to the Hugging Face API
4. **Response Parsing**: The AI response is parsed into quiz questions
5. **Database Storage**: Generated questions are saved to the database
6. **Fallback Handling**: If AI fails, fallback questions are used

## API Response Format

Success Response:
```json
{
  "success": true,
  "message": "AI quiz generated successfully",
  "data": {
    "id": 1,
    "student_id": "student_id",
    "questions": "[JSON array of questions]",
    "answers": "[]",
    "created_at": "timestamp",
    "updated_at": "timestamp"
  }
}
```

Error Response:
```json
{
  "success": false,
  "message": "Error message",
  "code": 500
}
```

## Troubleshooting

### "Invalid username or password" Error
1. Check that the Hugging Face API key in `.env` is correct
2. Verify the API key has access to the requested model
3. Ensure there are no extra spaces or characters in the API key

### Timeout Issues
1. Increase the timeout in `AIQuizService.php`:
   ```php
   ->timeout(60) // Increase from 30 to 60 seconds
   ```

### Fallback Questions
If the AI service fails, the system will automatically generate fallback questions to ensure the user always gets quiz questions.

## Files Modified

1. **[app/Services/AIQuizService.php](file:///d:/Edux-project/Edux-laravel/app/Services/AIQuizService.php)**: Fixed API call format and response handling
2. **[test_ai_prompt.py](file:///d:/Edux-project/Edux-laravel/test_ai_prompt.py)**: Updated to test Hugging Face API directly
3. **[test_ai_quiz_endpoint.py](file:///d:/Edux-project/Edux-laravel/test_ai_quiz_endpoint.py)**: Demonstrates proper Laravel endpoint testing
4. **[test_huggingface_api.py](file:///d:/Edux-project/Edux-laravel/test_huggingface_api.py)**: Tests Hugging Face API connectivity
5. **[simple_hf_test.py](file:///d:/Edux-project/Edux-laravel/simple_hf_test.py)**: Simple API connectivity test

## Environment Variables

Make sure your `.env` file contains:
```
HUGGINGFACE_API_KEY=your_actual_api_key_here
HUGGINGFACE_URL=https://router.huggingface.co/v1/chat/completions
```