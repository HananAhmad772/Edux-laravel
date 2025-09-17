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

class AdminServices
{
    use ApiResponses;
    protected $users;
    protected $profiles;

    public function __construct(UserRepository $users, ProfileRepository $profiles)
    {
        $this->users = $users;
        $this->profiles = $profiles;
    }

public function softDeleteUsers(array|int $ids): array
{
    // Convert single ID to array
    $ids = is_array($ids) ? $ids : [$ids];

    $deletedCount = $this->users->softDeleteUsersByIds($ids);

    return [
        'status' => true,
        'message' => "{$deletedCount} user(s) deleted successfully"
    ];
}

public function restoreUsers(array|int $ids): array
{
    $ids = is_array($ids) ? $ids : [$ids];

    $restoredCount = $this->users->restoreUsersByIds($ids);

    return [
        'status' => true,
        'message' => "{$restoredCount} user(s) restored successfully"
    ];
}


        public function updateUserStatus(string $userId, string $status): array
    {
        $user = $this->users->findById($userId);

        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found'
            ];
        }

        $this->users->updateStatus($user, $status);

        return [
            'status' => true,
            'message' => 'User status updated successfully'
        ];
    }

       public function getAllUsers(?string $userType, ?string $search, int $perPage = 10)
    {
        $users = $this->users->getAllUsers($userType, $search, $perPage);

        return [
            'status' => true,
            'message' => 'Users fetched successfully',
            'data' => $users
        ];
    }

     public function getDeletedUsers(int $perPage = 10): array
    {
        $users = $this->users->getDeletedUsers($perPage);

        return [
            'status' => true,
            'message' => 'Deleted users fetched successfully',
            'data' => $users
        ];
    }

     public function getUserById(string $userId, array $relations = []): array
    {
        $user = $this->users->findUserById($userId, $relations);

        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found',
                'data' => null
            ];
        }

        return [
            'status' => true,
            'message' => 'User fetched successfully',
            'data' => $user
        ];
    }
}