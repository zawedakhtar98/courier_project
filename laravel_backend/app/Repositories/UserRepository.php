<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interface\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function __construct() {}

    public function create(array $data)
    {
        return User::create($data);
    }

    public function update(array $data, int $id)
    {
        return User::where('id', $id)->update($data);
    }

    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }
}
