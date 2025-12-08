<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // Check if user is an agent and load agent data
        $agent = Agent::where('user_id', $user->id)->first();

        // If user has agent profile, validate it's approved
        if ($agent) {
            if ($agent->status !== 'approved') {
                throw ValidationException::withMessages([
                    'email' => ['Your agent account is pending approval.'],
                ]);
            }
        }

        // Crear token Sanctum
        $token = $user->createToken('login')->plainTextToken;

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?? 'user',
        ];

        // Add agent data if exists
        if ($agent) {
            $userData['agent'] = [
                'id' => $agent->id,
                'status' => $agent->status,
                'onboarding_status' => $agent->onboarding_status,
                'plan_id' => $agent->plan_id,
                'data' => $agent->data,
            ];
        }

        return response()->json([
            'success' => true,
            'user' => $userData,
            'token' => $token,
        ]);
    }

    /**
     * POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|in:user,agent',
            'agent_data' => 'nullable|array', // Optional agent data
        ]);

        DB::beginTransaction();

        try {
            $role = 0;
            $isAgent = isset($validated['role']) && $validated['role'] === 'agent';

            switch ($validated['role'] ?? 'user') {
                case 'user':
                    $role = 2;
                    break;
                case 'agent':
                    $role = 3; // Or use a specific agent role ID
                    break;
                default:
                    $role = 2;
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $role,
            ]);

            $agent = null;

            // If registering as agent, create agent record
            if ($isAgent) {
                $agentData = $validated['agent_data'] ?? [];

                $agent = Agent::create([
                    'user_id' => $user->id,
                    'status' => 'approved', // Auto-approve for now
                    'onboarding_status' => 'incomplete',
                    'plan_id' => null,
                    'data' => $agentData,
                ]);
            }

            DB::commit();

            $token = $user->createToken('register')->plainTextToken;

            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $validated['role'] ?? 'user',
            ];

            // Add agent data if created
            if ($agent) {
                $userData['agent'] = [
                    'id' => $agent->id,
                    'status' => $agent->status,
                    'onboarding_status' => $agent->onboarding_status,
                    'plan_id' => $agent->plan_id,
                    'data' => $agent->data,
                ];
            }

            return response()->json([
                'success' => true,
                'user' => $userData,
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * POST /api/auth/logout (auth)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
