<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentProfile;
use Symfony\Component\HttpFoundation\Response;

class CheckStudentProfile
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
                'code' => 401
            ], 401);
        }

        // Student-only platform: always check the student profile
        $studentProfile = StudentProfile::where('user_id', $user->id)->first();

        if (!$studentProfile) {
            // Check if this is the profile completion endpoint - allow it
            if ($request->is('api/student/questions') && $request->isMethod('post')) {
                return $next($request);
            }

            // For API requests, return JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please complete your profile setup first',
                    'code' => 403,
                    'profile_incomplete' => true,
                    'redirect_to' => '/student/profile-setup'
                ], 403);
            }

            // For web requests, redirect to profile setup
            return redirect('/student/profile-setup');
        }

        // Check if profile has required fields (basic validation)
        $requiredFields = ['major_subject', 'current_skill_level', 'main_goal'];
        $hasRequiredFields = true;

        foreach ($requiredFields as $field) {
            if (empty($studentProfile->$field)) {
                $hasRequiredFields = false;
                break;
            }
        }

        if (!$hasRequiredFields) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please complete your profile setup first',
                    'code' => 403,
                    'profile_incomplete' => true,
                    'redirect_to' => '/student/profile-setup'
                ], 403);
            }

            return redirect('/student/profile-setup');
        }

        return $next($request);
    }
}

