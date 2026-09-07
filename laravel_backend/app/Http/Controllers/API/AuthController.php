<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'string|required|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'string|required|min:6',
            'mobile_no' => 'required|string|max:15|unique:users,mobile_no',
            'gender' => 'required|in:male,female',
            'role' => 'required|in:admin,customer',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $user = $this->authService->register($request->all());

            $data = [
                'user' => new UserResource($user),
                'token' => $user->createToken('auth_token')->plainTextToken
            ];

            return ApiResponse::success($data, 'User created successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string',
        ]);
        try {
            $data = $this->authService->login($request->email, $request->password);
            $data['user'] = new UserResource($data['user']);
            return ApiResponse::success($data, 'User logged in successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function getLoginUser()
    {
        $user = auth()->user();
        return ApiResponse::success(new UserResource($user), 'User is already logged in!');
    }

    public function logout()
    {
        $user = auth()->user();
        $user->currentAccessToken()->delete();
        return ApiResponse::success([], 'User logged out successfully');
    }
}
