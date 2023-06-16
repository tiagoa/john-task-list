<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *      path="/register",
     *      tags={"User"},
     *      summary="Create user",
     *      description="Create a new user",
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              required={"name", "email", "password"},
     *              @OA\Property(property="name", type="string", format="text", example="John Galt"),
     *              @OA\Property(property="email", type="email", format="text", example="john.galt@gmail.com"),
     *              @OA\Property(property="password", type="string", format="text", example="My53cCre7P45Sw0rd"),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="User register",
     *          @OA\JsonContent(
     *              @OA\Property(property="access_token", type="string", example="1|ALrz0GZtqRLde6KlVjmWaKh1Ivn8W8WXHH3tlRIU"),
     *              @OA\Property(property="token_type", type="string", example="Bearer"),
     *          ),
     *     ),
     * )
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * @OA\Post(
     *      path="/login",
     *      summary="Login",
     *      description="Login user",
     *      tags={"User"},
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              required={"email", "password"},
     *              @OA\Property(property="email", type="email", format="text", example="john.galt@gmail.com"),
     *              @OA\Property(property="password", type="string", format="text", example="My53cCre7P45Sw0rd"),
     *          ),
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="User login",
     *          @OA\JsonContent(
     *              @OA\Property(property="access_token", type="string", example="3|QKIpnPo1KbBuKly4kAt23vrHHBRo7aQM38SS6A57"),
     *              @OA\Property(property="token_type", type="string", example="Bearer"),
     *          ),
     *     ),
     * )
     */
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

}
