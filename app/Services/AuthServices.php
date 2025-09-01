<?php
namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\ProfileRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Exception;

class AuthServices
{
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
        // We do not catch all exceptions here. Let Handler log and turn into generic response.
        return DB::transaction(function () use ($data) {
            $data['password'] = Hash::make($data['password']);

            $userPayload = [
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'],
                'password'   => $data['password'],
                'phone'      => $data['phone'],
                'user_type'  => $data['user_type'],
                'status'     => 'pending',
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
                    // store skills as json
                    if (isset($data['skills']) && is_array($data['skills'])) {
                        $data['skills'] = json_encode($data['skills']);
                    }
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
}
