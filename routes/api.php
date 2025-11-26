<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// });

Route::controller(AuthController::class)->prefix('auth')->group(function ( )
{
    Route::post('/signup','register');
    Route::post('/login','login');
    Route::post('/forgot/password', 'sendOtp');
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/reset-password', 'resetPassword');
});

Route::controller(AuthController::class)->prefix('auth')->middleware('auth:sanctum')->group(function ()
{
    Route::post('/logout','logout');
    Route::post('/logout-all', 'logoutfromAllDevices');
    Route::get('/profile', 'profile');
    Route::post('/change-password', 'changePassword');
    Route::post('/update-profile', 'updateProfile');
    
    // Profile completion endpoint - no profile check needed
    Route::post('/student/questions', 'updateStudentQuestions');
    
    // Student routes - require profile completion
    Route::middleware([\App\Http\Middleware\CheckStudentProfile::class])->group(function () {
        Route::post('/student/generate-quiz', 'generateAIQuiz');
        Route::post('/student/quiz', 'storeStudentQuiz');
        Route::get('/student/quizzes', 'getStudentQuizzes');
        Route::post('/student/generate-roadmap', 'generatePersonalizedRoadmap');
        Route::get('/student/roadmaps', 'getStudentRoadmaps');
        Route::get('/student/roadmap/latest', 'getLatestStudentRoadmap');
        Route::get('/student/roadmap/current', 'getCurrentRoadmapWithTopics');
        Route::post('/student/roadmap/advance', 'advanceUserProgress');
        Route::post('/student/chatbot', 'chatWithAI');
        Route::get('/student/chat/history', 'getChatHistory');
        Route::get('/student/dashboard', 'getDashboardData');
    });
});

Route::controller(AuthController::class)->prefix('admin')->middleware('admin')->group(function ()
{
    Route::post('/createUser', 'createUser');
    Route::post('/delete-account/{id}', 'deleteAccount');
    Route::post('/restore-account/{id}', 'restoreAccount');
    Route::get('/all-users', 'getAllUsers');
    Route::get('/all-deleted-users', 'getDeletedUsers');
    Route::get('/user/{id}', 'getUserById');
    Route::post('update-status/{id}','updateUserStatus');
});