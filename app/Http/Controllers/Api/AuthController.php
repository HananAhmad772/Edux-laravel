<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterationRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\StudentRegisterRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\StudentQuestionsRequest;
use App\Http\Requests\StudentQuizRequest;
use App\Http\Requests\AIChatbotRequest;
use App\Http\Resources\UserResources;
use App\Models\User;
use App\Services\AuthServices;
use App\Services\AdminServices;
use App\Services\ProfileService;
use App\Services\AIChatbotService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Http\Request as ModelRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponses;

    protected $authService;
    protected $adminService;
    protected $profileService;

    public function __construct(AuthServices $authService, AdminServices $adminService, ProfileService $profileService)
    {
        $this->authService = $authService;
        $this->adminService = $adminService;
        $this->profileService = $profileService;
    }

    public function register(RegisterationRequest $request)
    {
        try {

            $registrationRules = (new RegisterationRequest())->rules();
            $validator = Validator::make($request->all(), $registrationRules);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $validated = $validator->validated();

            // Platform is student-only: apply student-specific profile rules
            $profileRules = (new StudentRegisterRequest())->rules();
            $validator = Validator::make($request->all(), $profileRules);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $validated = array_merge($validated, $validator->validated());

            $user = $this->authService->register($validated);
            $this->authService->sendWelcomeVerificationEmail($user);
            
            // Create authentication token for the registered user
            Auth::login($user);
            $token = $user->createToken('auth_token')->plainTextToken;

            $userResources = new UserResources($user);

            $responseData = [
                'user' => $userResources,
                'token' => $token
            ];

            return $this->successResponse($responseData, 'User registered successfully', 201);

        } catch (\Throwable $th) {
            \Log::error('Registration failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('User registration failed. Please Try again');
        }
    }

    public function verifyEmail(Request $request, string $id, string $hash)
    {
        $user = User::find($id);

        if (!$user || !hash_equals(sha1($user->email), $hash)) {
            return response()->view('auth.email-verification-result', [
                'title' => 'Verification failed',
                'message' => 'The verification link is invalid or has expired.',
                'status' => 'error',
            ], 403);
        }

        if (is_null($user->email_verified_at)) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        return response()->view('auth.email-verification-result', [
            'title' => 'Email verified',
            'message' => 'Your email has been verified successfully. You can now use the platform.',
            'status' => 'success',
        ]);
    }

        public function createUser(RegisterationRequest $request)
    {
        try {

            $registrationRules = (new RegisterationRequest())->rules();
            $validator = Validator::make($request->all(), $registrationRules);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $validated = $validator->validated();

            // Platform is student-only: apply student-specific profile rules
            $profileRules = (new StudentRegisterRequest())->rules();
            $validator = Validator::make($request->all(), $profileRules);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $validated = array_merge($validated, $validator->validated());

            $user = $this->authService->register($validated);
            
            // Create authentication token for the registered user
            Auth::login($user);
            $token = $user->createToken('auth_token')->plainTextToken;

            $userResources = new UserResources($user);

            $responseData = [
                'user' => $userResources,
                'token' => $token
            ];

            return $this->successResponse($responseData, 'User registered successfully', 201);

        } catch (\Throwable $th) {
            \Log::error('Registration failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('User registration failed. Please Try again');
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $loginRules = (new LoginRequest())->rules();
            $validator = \Validator::make($request->all(), $loginRules);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray(), 'Validation failed');
            }

            $credentials = $validator->validated();

            if (!$data = $this->authService->login($credentials)) {
                return $this->errorResponse('Invalid credentials', 401);
            }

            return $this->successResponse($data, 'Login successful', 200);

        } catch (\Throwable $th) {
            \Log::error('Login failed: ' . $th->getMessage());
            return $this->errorResponse('User login failed. Please try again', 500);
        }
    }

    public function logoutfromalldevices(ModelRequest $request)
    {
        try {
            $user = $request->user();
            $user->tokens()->delete();

            return $this->successResponse(null, 'Logout successful from all devices', 200);
        } catch (\Throwable $th) {
            \Log::error('Logout failed: ' . $th->getMessage());
            return $this->errorResponse('User logout failed. Please Try again', 500);
        }
    }

    public function logout(ModelRequest $request)
    {
        try {
            $user = $request->user();

            // Delete only the token used in this request
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse(null, 'Logged out from current device successfully', 200);
        } catch (\Throwable $th) {
            \Log::error('Logout failed: ' . $th->getMessage());
            return $this->errorResponse('User logout failed. Please try again', 500);
        }
    }

   public function sendOtp(ForgotPasswordRequest $request)
    {

        $email = $request->email ?? null;
        $phone = $request->phone ?? null;

        if ($email) {
            $result = $this->authService->sendOtpToEmail($email);
        } elseif ($phone) {
            $result = $this->authService->sendOtpToPhone($phone); // you can create this
        } else {
            return $this->errorResponse('Email or Phone is required');
        }

        if (!$result['status']) {
            return $this->errorResponse($result['message']);
        }

        return $this->successResponse(null, $result['message']);
    }

    public function verifyOtp(VerifyOtpRequest $request)
    {
        // This is clean now
        return $this->authService->verifyOtp($request->email, $request->otp);
    }

     public function resetPassword(ResetPasswordRequest $request)
    {
        return $this->authService->resetPassword(
            $request->email,
            $request->otp,
            $request->password
        );
    }

    public function profile()
    {
        $user = User::with('studentProfile')
            ->find(auth()->id());

        return $this->successResponse(new UserResources($user), 'User profile fetched successfully');
    }

    public function updateProfile(ModelRequest $request)
    {
        try {
            $userId = auth()->id();
            
            // Validate the request
            $updateProfileRequest = new \App\Http\Requests\User\UpdateProfileRequest();
            $rules = $updateProfileRequest->rules();
            $messages = $updateProfileRequest->messages();
            
            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $validatedData = $validator->validated();

            // Update the profile
            $result = $this->profileService->updateProfile($userId, $validatedData);

            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            $userResources = new UserResources($result['data']);

            return $this->successResponse($userResources, $result['message']);

        } catch (\Throwable $th) {
            \Log::error('Profile update failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('Profile update failed. Please try again');
        }
    }

    public function deleteAccount(ModelRequest $request)
    {
        $ids = $request->input('ids'); // can be single ID or array of IDs

        if (empty($ids)) {
            return $this->errorResponse('No user IDs provided', 400);
        }

        $result = $this->adminService->softDeleteUsers($ids);

        return $this->successResponse(null, $result['message']);
    }

    public function restoreAccount(ModelRequest $request)
    {
        $ids = $request->input('ids'); // can be single ID or array of IDs

        if (empty($ids)) {
            return $this->errorResponse('No user IDs provided', 400);
        }

        $result = $this->adminService->restoreUsers($ids);

        return $this->successResponse(null, $result['message']);
    }


    public function changePassword(ModelRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $result = $this->authService->changePassword(
            auth()->id(),
            $request->current_password,
            $request->password
        );

        if (!$result['status']) {
            return $this->errorResponse($result['message'], $result['code']);
        }

        return $this->successResponse(null, $result['message']);
    }

    public function updateUserStatus(ModelRequest $request, $userId)
    {
        $request->validate([
            'status' => 'required|string|in:pending,under_review,rejected,locked,approved'
        ]);

        $result = $this->adminService->updateUserStatus($userId, $request->status);

        if (!$result['status']) {
            return $this->errorResponse($result['message'], 404);
        }

        return $this->successResponse(null, $result['message']);
    }

    public function getDeletedUsers(ModelRequest $request)
    {
        $perPage = $request->query('per_page', 10);

        $result = $this->adminService->getDeletedUsers($perPage);

        return $this->successResponse($result['data'], $result['message']);
    }

    public function getUserById($id)
    {
        // Add relationships if needed
        $relations = ['studentProfile'];

        $result = $this->adminService->getUserById($id, $relations);

        if (!$result['status']) {
            return $this->errorResponse($result['message'], 404);
        }

        return $this->successResponse($result['data'], $result['message']);
    }
    
    public function updateStudentQuestions(StudentQuestionsRequest $request)
    {
        try {
            $userId = auth()->id();
            
            $validatedData = $request->validated();
            
            // Update the student profile with questions data
            $result = $this->profileService->updateStudentQuestions($userId, $validatedData);

            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Throwable $th) {
            \Log::error('Student questions update failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('Student questions update failed. Please try again');
        }
    }
    
    public function storeStudentQuiz(StudentQuizRequest $request)
    {
        try {
            $userId = auth()->id();
            
            $validatedData = $request->validated();
            
            // Store the student quiz data
            $result = $this->profileService->storeStudentQuiz($userId, $validatedData);

            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            return $this->successResponse($result['data'], $result['message'], $result['code']);

        } catch (\Throwable $th) {
            \Log::error('Student quiz storage failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('Student quiz storage failed. Please try again');
        }
    }
    
    public function getStudentQuizzes(ModelRequest $request)
    {
        try {
            $userId = auth()->id();
            
            // Get the student quizzes
            $result = $this->profileService->getStudentQuizzes($userId);

            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Throwable $th) {
            \Log::error('Getting student quizzes failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('Getting student quizzes failed. Please try again');
        }
    }
    
    /**
     * Generate AI quiz for the student
     */
    public function generateAIQuiz(ModelRequest $request)
    {
        try {
            $userId = auth()->id();
            
            // Generate AI quiz based on student profile
            $result = $this->profileService->generateAIQuiz($userId);

            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Throwable $th) {
            \Log::error('AI quiz generation failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('AI quiz generation failed. Please try again');
        }
    }
    
    /**
     * Generate personalized learning roadmap for the student
     */
    public function generatePersonalizedRoadmap(ModelRequest $request)
    {
        try {
            $userId = auth()->id();
            
            \Log::info('Roadmap generation endpoint called', ['user_id' => $userId]);
            
            // Generate personalized learning roadmap based on student profile and quiz data
            $result = $this->profileService->generatePersonalizedRoadmap($userId);

            if (!$result['status']) {
                \Log::warning('Roadmap generation endpoint failed', [
                    'user_id' => $userId,
                    'error_message' => $result['message'],
                    'error_code' => $result['code']
                ]);
                return $this->errorResponse($result['message'], $result['code']);
            }

            \Log::info('Roadmap generation endpoint successful', [
                'user_id' => $userId,
                'roadmap_id' => $result['data']['id'] ?? null
            ]);

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Throwable $th) {
            \Log::error('Personalized roadmap generation failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('Personalized roadmap generation failed. Please try again');
        }
    }
    
    /**
     * Get all roadmaps for the student
     */
    public function getStudentRoadmaps(ModelRequest $request)
    {
        try {
            $userId = auth()->id();
            
            // Get all student roadmaps
            $result = $this->profileService->getStudentRoadmaps($userId);

            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Throwable $th) {
            \Log::error('Getting student roadmaps failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('Getting student roadmaps failed. Please try again');
        }
    }
    
    /**
     * Get the latest roadmap for the student
     */
    public function getLatestStudentRoadmap(ModelRequest $request)
    {
        try {
            $userId = auth()->id();
            
            // Get the latest student roadmap
            $result = $this->profileService->getLatestStudentRoadmap($userId);

            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Throwable $th) {
            \Log::error('Getting latest student roadmap failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('Getting latest student roadmap failed. Please try again');
        }
    }
    
    /**
     * Get current roadmap with today's, yesterday's, and tomorrow's topics
     */
    public function getCurrentRoadmapWithTopics(ModelRequest $request)
    {
        try {
            $userId = auth()->id();
            
            // Get current roadmap with topics
            $result = $this->profileService->getCurrentRoadmapWithTopics($userId);

            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            // Format the response to match the required structure
            $data = $result['data'];
            
            // Prepare the response with today, yesterday, and tomorrow topics
            $formattedResponse = [
                // 'today' => $data['today'],
                // 'yesterday' => $data['yesterday'],
                // 'tomorrow' => $data['tomorrow'],
                'day_wise_roadmap' => $data['day_wise_roadmap'],
                'current_progress' => $data['current_progress'],
                'student' => new UserResources($data['student'])
            ];

            return $this->successResponse($formattedResponse, $result['message']);

        } catch (\Throwable $th) {
            \Log::error('Getting current roadmap with topics failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('Getting current roadmap with topics failed. Please try again');
        }
    }
    
    /**
     * Advance user progress to the next topic
     */
    public function advanceUserProgress(ModelRequest $request)
    {
        try {
            $userId = auth()->id();
            
            // Advance user progress
            $result = $this->profileService->advanceUserProgress($userId);

            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Throwable $th) {
            \Log::error('Advancing user progress failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('Advancing user progress failed. Please try again');
        }
    }
    
    /**
     * Chat with AI mentor
     */
    public function chatWithAI(AIChatbotRequest $request)
    {
        try {
            $userId = auth()->id();
            
            $messages = $request->input('messages');
            
            // Generate response from AI with topic restrictions
            $result = $this->profileService->chatWithAI($userId, $messages);
            
            if ($result['status']) {
                return $this->successResponse([
                    'response' => $result['data']['response']
                ], $result['message']);
            } else {
                return $this->errorResponse($result['message'], 500);
            }
            
        } catch (\Throwable $th) {
            \Log::error('AI Chatbot failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('AI Chatbot failed. Please try again');
        }
    }
    
    /**
     * Generate daily challenge for the student
     */
    public function generateDailyChallenge(ModelRequest $request)
    {
        try {
            $userId = auth()->id();
            
            // Generate daily challenge
            $result = $this->profileService->generateDailyChallenge($userId);

            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            // Extract structured data for frontend integration
            $challenge = $result['data'];
            $challengeData = $challenge['challenge_data'] ?? [];
            
            // Prepare the response with the exact parameters needed for frontend
            $formattedResponse = [
                'id' => $challenge['id'],
                'topic' => $challenge['topic_name'],
                'description' => $challenge['challenge_description'],
                'instructions' => $challengeData['instructions'] ?? '',
                'expected_outcome' => $challengeData['expected_outcome'] ?? '',
                'tips' => $challengeData['tips'] ?? '',
                'difficulty' => $challengeData['difficulty'] ?? 'Intermediate',
                'estimated_time' => $challengeData['estimated_time'] ?? '20 minutes',
                'solution' => $challengeData['solution'] ?? '',
                'is_completed' => $challenge['is_completed'],
                'points_earned' => $challenge['points_earned'],
                'created_at' => $challenge['created_at'],
                'updated_at' => $challenge['updated_at']
            ];

            return $this->successResponse($formattedResponse, $result['message']);

        } catch (\Throwable $th) {
            \Log::error('Generating daily challenge failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('Generating daily challenge failed. Please try again');
        }
    }
    
    /**
     * Submit answer for daily challenge
     */
    public function submitDailyChallenge(Request $request)
    {
        try {
            $request->validate([
                'challenge_id' => 'required|string',
                'submission' => 'required|string'
            ]);
            
            $challengeId = $request->input('challenge_id');
            $submission = $request->input('submission');
            
            // Evaluate submission
            $result = $this->profileService->evaluateDailyChallengeSubmission($challengeId, $submission);

            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            // Extract structured data for frontend integration
            $challenge = $result['data'];
            $challengeData = $challenge['challenge_data'] ?? [];
            
            // Prepare the response with the exact parameters needed for frontend
            $formattedResponse = [
                'id' => $challenge['id'],
                'topic' => $challenge['topic_name'],
                'description' => $challenge['challenge_description'],
                'instructions' => $challengeData['instructions'] ?? '',
                'expected_outcome' => $challengeData['expected_outcome'] ?? '',
                'tips' => $challengeData['tips'] ?? '',
                'difficulty' => $challengeData['difficulty'] ?? 'Intermediate',
                'estimated_time' => $challengeData['estimated_time'] ?? '20 minutes',
                'solution' => $challengeData['solution'] ?? '',
                'student_submission' => $challenge['student_submission'],
                'ai_feedback' => $challenge['ai_feedback'],
                'is_completed' => $challenge['is_completed'],
                'points_earned' => $challenge['points_earned'],
                'created_at' => $challenge['created_at'],
                'updated_at' => $challenge['updated_at']
            ];

            return $this->successResponse($formattedResponse, $result['message']);

        } catch (\Throwable $th) {
            \Log::error('Submitting daily challenge failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('Submitting daily challenge failed. Please try again');
        }
    }
    
    /**
     * Parse challenge description that may contain nested JSON
     *
     * @param string $description
     * @return array
     */
    private function parseChallengeDescription($description)
    {
        // Since we're now formatting the data properly in the service, 
        // we don't need to parse it here anymore
        return ['description' => $description];
    }
    
    /**
     * Get dashboard data for the student
     */
    public function getProgress(ModelRequest $request)
    {
        try {
            $userId = auth()->id();
            
            // Get extended dashboard data from the extended dashboard service
            $extendedDashboardService = new \App\Services\ExtendedDashboardService();
            $result = $extendedDashboardService->getExtendedDashboardData($userId);

            if (!$result['status']) {
                return $this->errorResponse($result['message'], $result['code']);
            }

            return $this->successResponse($result['data'], $result['message']);

        } catch (\Throwable $th) {
            \Log::error('Getting dashboard data failed: ' . $th->getMessage());
            return $this->internalServerErrorResponse('Getting dashboard data failed. Please try again');
        }
    }
    
}