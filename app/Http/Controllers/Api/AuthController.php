<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompanyRegisterRequest;
use App\Http\Requests\Auth\MentorRegisterRequest;
use App\Http\Requests\Auth\ProfessionalRegisterRequest;
use App\Http\Requests\Auth\RegisterationRequest;
use App\Http\Requests\Auth\StudentRegisterRequest;
use App\Http\Resources\UserResources;
use App\Services\AuthServices;
use Illuminate\Http\Request as ModelRequest;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // use ApiResponse;

    protected $authService;

    public function __construct(AuthServices $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterationRequest $request)
    {
        try {
            //code...
        
        // validated base data
        $validated = $request->validated();
        $type = $validated['user_type'];

        // perform type-specific validation by using the FormRequest rules
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

        // merge additional rules and validate
        if (!empty($profileRules)) {
            $validator = Validator::make($request->all(), $profileRules);
          if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

            $validated = array_merge($validated, $validator->validated());
        }

        // call service (service uses DB::transaction)
        $user = $this->authService->register($validated);

        $userResources = new UserResources($user);

        // return success with standardized resource
        return $this->successResponse($user, 'User registered successfully', 201);

        } catch (\Throwable $th) {
            \Log::error('Registration failed: ' . $th->getMessage());
            return $this->errorResponse('User registration failed. Please Try again', 500);
        }
    }
}
