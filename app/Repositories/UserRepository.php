<?php
namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function create(array $data): User
    {
        // make sure password is hashed before sending
        return User::create($data);
    }

    public function findById($id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
