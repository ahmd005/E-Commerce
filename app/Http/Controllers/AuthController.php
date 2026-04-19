<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\AuthValidation\RegisterValidation;
use App\Http\Requests\AuthValidation\LoginValidation;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function register(RegisterValidation $request)
    {

        $validation = $request->validated();
        $user = $this->users->making([
            'name' => $validation['name'],
            'email' => $validation['email'],
            'password' => Hash::make($validation['password']),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ], 201);
    }

    public function login(LoginValidation $request)
    {
        $validation = $request->validated();

        $user = $this->users->findByEmail($validation['email']);

        if (!$user || !Hash::check($validation['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token
        ]);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'],201);
    }
}
