<?php
namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    public function create(array $data)
    {
        // make sure password is hashed before sending
        return User::create($data);
    }

    public function findById($id)
    {
        return User::find($id);
    }

    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

     public function findByPhone(string $phone)
    {
        return User::where('phone', $phone)->first();
    }

     public function updateOtp($user, $otp, $expiry)
    {
        return $user->update([
            'reset_password_otp' => $otp,
            'reset_password_otp_expiry' => $expiry,
        ]);
    }

      public function updatePasswordAndClearOtp(User $user, string $hashedPassword)
    {
        return $user->update([
            'password' => $hashedPassword, 
            'reset_password_otp' => null,
            'reset_password_otp_expiry'=> null
        ]);
    }

    public function softDeleteUsersByIds(array $ids): int
    {
        return User::whereIn('id', $ids)->delete();
    }

    public function restoreUsersByIds(array $ids): int
    {
        return User::withTrashed()
            ->whereIn('id', $ids)
            ->restore();
    }

     public function updatePassword(User $user, string $newPassword)
    {
        return $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

      public function updateStatus(User $user, string $status): bool
    {
        return $user->update(['status' => $status]);
    }

      public function getAllUsers(?string $userType = null, ?string $search = null, int $perPage = 10)
    {
        $query = User::query()
            ->where('is_admin', false)         // Exclude admin
            ->whereNull('deleted_at');         // Exclude soft-deleted users

        // Search by name or email
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function getDeletedUsers(int $perPage = 10)
    {
        return User::onlyTrashed()
            ->where('is_admin', false)
            ->paginate($perPage);
    }

        public function findUserById(string $id, array $relations = [])
    {
        return User::with($relations)->find($id);
    }

}
