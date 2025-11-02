<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompanyRegisterRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\MentorRegisterRequest;
use App\Http\Requests\Auth\ProfessionalRegisterRequest;
use App\Http\Requests\Auth\RegisterationRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\StudentRegisterRequest;
use App\Http\Requests\StudentQuestionsRequest;
use App\Http\Requests\StudentQuizRequest;
use App\Http\Resources\UserResources;
use App\Models\User;
use App\Services\AuthServices;
use App\Services\AdminServices;
use App\Services\ProfileService;
use App\Traits\ApiResponses;
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
            $type = $validated['user_type'];

            $profileRules = [];
            switch ($type) {
                case 'student':
                    $profileRules = (new StudentRegisterRequest())->rules();
                    break;
                case 'mentor':
                    $profileRules = (new MentorRegisterRequest())->rules();
                    break;
                case 'professional':
                    $profileRules = (new ProfessionalRegisterRequest())->rules();
                    break;
                case 'company':
                    $profileRules = (new CompanyRegisterRequest())->rules();
                    break;
            }

            if (!empty($profileRules)) {
                $validator = Validator::make($request->all(), $profileRules);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors()->toArray());
                }

                $validated = array_merge($validated, $validator->validated());
            }

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

        public function createUser(RegisterationRequest $request)
    {
        try {

            $registrationRules = (new RegisterationRequest())->rules();
            $validator = Validator::make($request->all(), $registrationRules);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->toArray());
            }

            $validated = $validator->validated();
            $type = $validated['user_type'];

            $profileRules = [];
            switch ($type) {
                case 'student':
                    $profileRules = (new StudentRegisterRequest())->rules();
                    break;
                case 'mentor':
                    $profileRules = (new MentorRegisterRequest())->rules();
                    break;
                case 'professional':
                    $profileRules = (new ProfessionalRegisterRequest())->rules();
                    break;
                case 'company':
                    $profileRules = (new CompanyRegisterRequest())->rules();
                    break;
            }

            if (!empty($profileRules)) {
                $validator = Validator::make($request->all(), $profileRules);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors()->toArray());
                }

                $validated = array_merge($validated, $validator->validated());
            }

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
        $user = User::with('studentProfile', 'mentorProfile', 'professionalProfile', 'companyProfile')
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
        $relations = ['studentProfile', 'mentorProfile', 'professionalProfile', 'companyProfile'];

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
}