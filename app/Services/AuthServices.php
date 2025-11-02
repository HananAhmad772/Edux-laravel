<?php
namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\ProfileRepository;
use App\Traits\ApiResponses;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Exception;

class AuthServices
{
    use ApiResponses;
    protected $users;
    protected $profiles;

    public function __construct(UserRepository $users, ProfileRepository $profiles)
    {
        $this->users = $users;
        $this->profiles = $profiles;
    }

    /**
     * Register user and profile in a transaction.
     * Throws exceptions on failure (handled globally).
     */
    public function register(array $data)
    {

        return DB::transaction(function () use ($data) {
            $data['password'] = Hash::make($data['password']);

            $userPayload = [
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'],
                'password'   => $data['password'],
                'phone'      => $data['phone'],
                'user_type'  => $data['user_type'],
                'status'     => 'approved',
            ];

            $user = $this->users->create($userPayload);

            // create profile based on type
            switch ($data['user_type']) {
                case 'student':
                    $this->profiles->createStudent(array_merge($data, ['user_id' => $user->id]));
                    $user->load('studentProfile');
                    break;
                case 'mentor':
                    $this->profiles->createMentor(array_merge($data, ['user_id' => $user->id]));
                    $user->load('mentorProfile');
                    break;
                case 'professional':
                    $this->profiles->createProfessional(array_merge($data, ['user_id' => $user->id]));
                    $user->load('professionalProfile');
                    break;
                case 'company':
                    $this->profiles->createCompany(array_merge($data, ['user_id' => $user->id]));
                    $user->load('companyProfile');
                    break;
                default:
                    throw new Exception('Invalid user type');
            }

            return $user->refresh();
        });
    }

    public function login(array $credentials)
    {
        $userType = $credentials['user_type'];

        $user = User::where('email', $credentials['email'])
                    ->where('user_type', $userType)
                    ->first();

        if (!$user) {
            return false; 
        }

        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            $user = Auth::user();

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user'  => $user,
                'token' => $token,
                // 'type'  => 'Bearer'
            ];
        }

        return false;
    }

     public function sendOtpToEmail($email)
    {
        // Find user by email
        $user = $this->users->findByEmail($email);

        if (!$user) {
            return ['status' => false, 'message' => 'User Not Found'];
        }

        // Generate OTP + expiry
        $otp = rand(100000, 999999);
        $expiry = now()->addMinutes(10);

        // Update in DB
        $this->users->updateOtp($user, $otp, $expiry);

        // Email data
        $emailData = [
            'userName' => $user->name,
            'otp'      => $otp,
        ];

        // Send email
        try {
            Mail::send(
                ['html' => 'emails.reset_otp', 'text' => 'emails.reset_otp_text'],
                $emailData,
                function ($message) use ($user) {
                    $message->to($user->email, $user->name)
                            ->subject('Reset Your Password - OTP Code');
                }
            );
        } catch (\Exception $mailException) {
            Log::error('Reset password OTP mail failed', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'error'   => $mailException->getMessage(),
            ]);
        }

        return ['status' => true, 'message' => 'OTP has been sent to your Email. Please Verify.'];
    }

        public function sendOtpToPhone($phone)
    {
        // Find user by phone
        $user = $this->users->findByPhone($phone); // add this in UserRepository if not already

        if (!$user) {
            return ['status' => false, 'message' => 'User Not Found'];
        }

        // Generate OTP + expiry
        $otp = rand(100000, 999999);
        $expiry = now()->addMinutes(10);

        // Update in DB
        $this->users->updateOtp($user, $otp, $expiry);

        // Send OTP via SMS
        try {
            // Example with a hypothetical SMS service class
            // You can integrate Twilio, Nexmo, or any other SMS gateway here
            app('sms')->send($user->phone, "Your OTP Code is: {$otp}. It expires in 10 minutes.");
        } catch (\Exception $smsException) {
            Log::error('Reset password OTP SMS failed', [
                'user_id' => $user->id,
                'phone'   => $user->phone,
                'error'   => $smsException->getMessage(),
            ]);
        }

        return ['status' => true, 'message' => 'OTP has been sent to your Phone. Please Verify.'];
    }

     public function verifyOtp(string $email, string $otp)
    {
        $user = $this->users->findByEmail($email);

        if (!$user || $user->reset_password_otp !== $otp) {

            return $this->errorResponse('Invalid OTP', 400);
        }

        if (Carbon::now()->gt($user->reset_password_otp_expiry)) {

            return $this->errorResponse('Expired OTP', 400);
        }

        return $this->successResponse(null, 'OTP verified, you can reset your password now.');
    }

      public function resetPassword(string $email, string $otp, string $newPassword)
    {
        $user = $this->users->findByEmail($email);

        if (!$user || $user->reset_password_otp !== $otp) {
            return $this->errorResponse('Invalid OTP or Expired OTP', 400);
        }

        if (Carbon::now()->gt($user->reset_password_otp_expiry)) {
            return $this->errorResponse('Invalid OTP or Expired OTP', 400);
        }

        // Hash the password
        $hashedPassword = Hash::make($newPassword);

        // Update password & clear OTP
        $this->users->updatePasswordAndClearOtp($user, $hashedPassword);

        return $this->successResponse(null, 'Password has been successfully updated.');
    }

    public function changePassword($userId, string $currentPassword, string $newPassword): array
    {
        $user = $this->users->findById($userId);

        if (!$user) {
            return [
                'status'  => false,
                'message' => 'User Not Found',
                'code'    => 404
            ];
        }

        if (!Hash::check($currentPassword, $user->password)) {
            return [
                'status'  => false,
                'message' => 'Current password is incorrect',
                'code'    => 403
            ];
        }

        $this->users->updatePassword($user, $newPassword);

        return [
            'status'  => true,
            'message' => 'Password Updated Successfully',
            'code'    => 200
        ];
    }
    
}
