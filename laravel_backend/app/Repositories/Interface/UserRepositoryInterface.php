<?php

namespace App\Repositories\Interface;

interface UserRepositoryInterface
{
    public function create(array $data);
    public function update(array $data, int $id);
    public function findByEmail(string $email);
}
