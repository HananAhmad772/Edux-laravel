# Edux - AI Powered Learning Platform

Edux is an AI-powered learning platform where students can learn from AI, get certificates, and then companies can reach out to them for internships.

## Features Implemented

### Student Module
- Student registration with basic information
- Student profile management
- AI-powered learning and certificate generation
- Quiz system for skill assessment

### Company Module
- Company registration
- Job posting functionality
- Access to student profiles for recruitment

### Admin Module
- User management
- Profile verification
- System monitoring

## Recent Updates

### Database Schema Changes
1. Enhanced `student_profiles` table with additional fields:
   - `current_position`
   - `specialization_field`
   - `preferred_technologies`
   - `current_skill_level`
   - `main_goal`
   - `time_per_week`

2. Created new `student_quiz` table for storing quiz data:
   - `questions`
   - `answers`
   - `score`

### API Endpoints Added
- `POST /auth/student/questions` - Update student profile questions
- `POST /auth/student/quiz` - Store student quiz results
- `GET /auth/student/quizzes` - Retrieve student quizzes

### API Documentation
- Swagger UI: `GET /docs`
- OpenAPI JSON: `GET /docs/openapi.json`
- The documentation covers authentication, student learning flows, and badges with the shared API response format (`code`, `message`, `success`, `data`).

## Installation

1. Clone the repository
2. Run `composer install`
3. Run `npm install`
4. Copy `.env.example` to `.env` and configure your database
5. Run `php artisan key:generate`
6. Run `php artisan migrate`
7. Run `php artisan db:seed` (if needed)

## Running the Application

- `npm run dev` - Start the development server
- `php artisan serve` - Start the Laravel server
- `php artisan queue:listen` - Start the queue worker

## Testing

- Run `php artisan test` to execute all tests

## Technology Stack

- Laravel 12
- PHP 8.2+
- Laravel Sanctum for API authentication
- Vite for frontend asset management
- Tailwind CSS for styling
- Pest for testing

## Implementation Details

For detailed information about the recent implementation, see:
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md)
- [Implementation Complete](IMPLEMENTATION_COMPLETE.md)
- [Final Implementation Summary](FINAL_IMPLEMENTATION_SUMMARY.md)

## License

This project is proprietary and confidential. All rights reserved.