<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Hobby;
use App\Models\Interest;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:50|unique:users,phone',
            'age' => 'required|integer|min:18|max:100',
            'bio' => 'required|string|min:10',
            'gender' => 'required|string',
            'looking_for' => 'required|string',
            'relationship_goal' => 'required|string',
            'education' => 'nullable|string',
            'occupation' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'height' => 'nullable|string',
            'religion' => 'nullable|string',
            'smoking' => 'nullable|string',
            'drinking' => 'nullable|string',
            'has_children' => 'boolean',
            'zodiac_sign' => 'nullable|string',
            'hobbies' => 'array',
            'hobbies.*' => 'string',
            'interests' => 'array',
            'interests.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => bcrypt($request->password),
                    'phone' => $request->phone,
                    'age' => $request->age,
                    'bio' => $request->bio,
                    'gender' => $request->gender,
                    'looking_for' => $request->looking_for,
                    'relationship_goal' => $request->relationship_goal,
                    'education' => $request->education,
                    'occupation' => $request->occupation,
                    'city' => $request->city,
                    'country' => $request->country,
                    'height' => $request->height,
                    'religion' => $request->religion,
                    'smoking' => $request->smoking,
                    'drinking' => $request->drinking,
                    'has_children' => (bool) $request->has_children,
                    'zodiac_sign' => $request->zodiac_sign,
                ]);

                // Attach hobbies
                $hobbyIds = [];
                foreach ((array) $request->hobbies as $hobbyName) {
                    $hobby = Hobby::firstOrCreate(['name' => $hobbyName]);
                    $hobbyIds[] = $hobby->id;
                }
                if (!empty($hobbyIds)) {
                    $user->hobbies()->sync($hobbyIds);
                }

                // Attach interests
                $interestIds = [];
                foreach ((array) $request->interests as $interestName) {
                    $interest = Interest::firstOrCreate(['name' => $interestName]);
                    $interestIds[] = $interest->id;
                }
                if (!empty($interestIds)) {
                    $user->interests()->sync($interestIds);
                }

                $token = $user->createToken('AppToken')->accessToken;

                $user->load(['hobbies', 'interests', 'photos']);

                return response()->json([
                    'success' => true,
                    'message' => 'Registration successful',
                    'token' => $token,
                    'user' => $user,
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login with email and password.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Attempt to authenticate user
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $token = $user->createToken('AppToken')->accessToken;
            $user->updateOnlineStatus(true);
            $user->load(['hobbies', 'interests', 'photos']);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
        ], 401);
    }



    /**
     * Logout current user by revoking token.
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user) {
                // Attempt to revoke current access token if available
                $token = $user->token();
                if ($token) {
                    $token->revoke();
                }
                $user->updateOnlineStatus(false);
            }
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
