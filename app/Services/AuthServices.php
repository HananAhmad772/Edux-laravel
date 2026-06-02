<?php
namespace App\Services;

use App\Mail\WelcomeVerificationMail;
use App\Models\User;
use App\Models\Badge;
use App\Models\UserBadge;
use App\Repositories\UserRepository;
use App\Repositories\ProfileRepository;
use App\Traits\ApiResponses;
use App\Services\BadgeService;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Exception;

class AuthServices
{
    use ApiResponses;
    protected $users;
    protected $profiles;
    protected $badgeService;

    public function __construct(UserRepository $users, ProfileRepository $profiles)
    {
        $this->users = $users;
        $this->profiles = $profiles;
        $this->badgeService = new BadgeService();
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
                'user_type'  => 'student',
                'status'     => 'approved',
            ];

            $user = $this->users->create($userPayload);

            // Platform is student-only: create student profile
            $this->profiles->createStudent(array_merge($data, ['user_id' => $user->id]));
            $user->load('studentProfile');

            return $user->refresh();
        });
    }

    public function sendWelcomeVerificationEmail(User $user): void
    {
        try {
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addDay(),
                [
                    'id' => $user->id,
                    'hash' => sha1($user->email),
                ]
            );

            Mail::to($user->email)->queue(new WelcomeVerificationMail($user, $verificationUrl));
        } catch (\Throwable $exception) {
            Log::error('Welcome verification mail failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function login(array $credentials)
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return false; 
        }

        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            $user = Auth::user();

            // Check and award badges based on user progress
            $this->badgeService->checkAndAwardBadges($user);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user'  => $user,
                'token' => $token,
                // 'type'  => 'Bearer'
            ];
        }

        return false;
    }

    /**
     * Award the "First Login" badge to a user if they haven't received it yet
     */
    private function awardFirstLoginBadge($user)
    {
        try {
            // Find the "First Login" badge
            $firstLoginBadge = Badge::where('name', 'First Login')->first();
            
            if (!$firstLoginBadge) {
                // Badge doesn't exist, create it
                $firstLoginBadge = Badge::create([
                    'name' => 'First Login',
                    'description' => 'Completed registration',
                    'icon' => '🎉',
                    'criteria_type' => 'registration',
                    'criteria_value' => 1
                ]);
            }
            
            // Check if user already has this badge
            $existingUserBadge = UserBadge::where('user_id', $user->id)
                ->where('badge_id', $firstLoginBadge->id)
                ->first();
                
            if (!$existingUserBadge) {
                // Award the badge
                UserBadge::create([
                    'user_id' => $user->id,
                    'badge_id' => $firstLoginBadge->id,
                    'earned_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the login process
            Log::error('Failed to award First Login badge: ' . $e->getMessage());
        }
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
