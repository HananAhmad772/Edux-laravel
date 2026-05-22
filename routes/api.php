<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BadgeController;
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
});

Route::controller(AuthController::class)->prefix('student')->middleware('auth:sanctum')->group(function ()
{
    Route::get('/profile', 'profile');
    Route::post('/change-password', 'changePassword');
    Route::post('/update-profile', 'updateProfile');
    
    // Profile completion endpoint - no profile check needed
    Route::post('/questions', 'updateStudentQuestions');
    
    // Student routes - require profile completion
    Route::middleware([\App\Http\Middleware\CheckStudentProfile::class])->group(function () {
        Route::post('/generate-quiz', 'generateAIQuiz');
        Route::post('/quiz', 'storeStudentQuiz');
        Route::get('/quizzes', 'getStudentQuizzes');
        Route::post('/generate-roadmap', 'generatePersonalizedRoadmap');
        Route::get('/roadmaps', 'getStudentRoadmaps');
        Route::get('/roadmap/latest', 'getLatestStudentRoadmap');
        Route::get('/roadmap/current', 'getCurrentRoadmapWithTopics');
        Route::post('/roadmap/advance', 'advanceUserProgress');
        Route::post('/chatbot', 'chatWithAI');
        Route::get('/chat/history', 'getChatHistory');
        Route::get('/progress', 'getProgress');
        
        // Daily Challenge routes
        Route::get('/daily-challenge', 'generateDailyChallenge');
        Route::post('/daily-challenge/submit', 'submitDailyChallenge');
    });
});

Route::controller(BadgeController::class)->prefix('badges')->middleware('auth:sanctum')->group(function () {
    Route::get('/', 'index');
    Route::get('/user', 'userBadges');
    Route::post('/award', 'awardBadge');
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