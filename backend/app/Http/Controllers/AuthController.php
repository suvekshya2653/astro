<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // =======================
    // REGISTER
    // =======================
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'gender'   => 'required|string',
        ]);

        $user = User::create([
            'name'     => $validatedData['name'],
            'email'    => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'gender'   => $validatedData['gender'],
            'role'     => 'customer',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    // =======================
    // LOGIN (Admin + Customer)
    // =======================
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        Log::info("Login attempt: " . $request->email);

        // -----------------------------------------
        // ⭐ HARDCODED ADMIN LOGIN
        // -----------------------------------------
        if (
            $request->email === 'sarita123@gmail.com' &&
            $request->password === 'iamsaritaghimira'
        ) {
            Log::info("Admin login detected");

            $admin = User::firstOrCreate(
                ['email' => 'sarita123@gmail.com'],
                [
                    'name' => 'Sarita Ghimira',
                    'password' => Hash::make('iamsaritaghimira'),
                    'gender' => 'female',
                    'role' => 'admin',
                ]
            );

            // Force admin role
            if ($admin->role !== 'admin') {
                $admin->update(['role' => 'admin']);
            }

            $token = $admin->createToken('admin_token')->plainTextToken;

            return response()->json([
                'message' => 'Admin login successful',
                'token'   => $token,
                'user'    => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'gender' => $admin->gender,
                    'role' => 'admin',   // ← FIX
                ],
            ], 200);
        }

        // -----------------------------------------
        // 🔹 NORMAL CUSTOMER LOGIN
        // -----------------------------------------
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning("Login failed for: " . $request->email);
            return response()->json(['message' => 'Invalid login credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'gender' => $user->gender,
                'role' => $user->role ?? 'customer',   // ← FIX
            ],
        ]);
    }

    // =======================
    // LOGOUT
    // =======================
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
