<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\ToManyLoginAttemptsException;
use App\Repositories\Interface\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected UserRepositoryInterface $userRepository) {}

    public function findByUserEmail(string $email)
    {
        return $this->userRepository->findByEmail($email);
    }

    public function register(array $data)
    {
        $data['password'] =  Hash::make($data['password']);
        $user = $this->userRepository->create($data);

        return $user;
    }

    public function login(string $email, string $password)
    {

        $key = $this->throttleKey($email, request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw new ToManyLoginAttemptsException('Too many login attempts. Please try again later.');
        }
        $user = $this->userRepository->findByEmail($email);
        if (!$user || !Hash::check($password, $user->password)) {
            RateLimiter::hit($key, 60);
            throw new InvalidCredentialsException('Invalid credentials.');
        }
        RateLimiter::clear($key);
        return [
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken
        ];
    }

    protected function throttleKey(string $email, string $ip)
    {
        return Str::lower($email . '|' . $ip);
    }
}
