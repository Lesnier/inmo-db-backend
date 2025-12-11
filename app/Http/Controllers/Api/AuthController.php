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

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for User Authentication"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="User Login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="token", type="string", example="1|laravel_sanctum_token..."),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid login details'], 401);
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
            'role' => $user->role, // Returns Role object (lazy loaded if needed)
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
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="User Registration",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="secret"),
     *             @OA\Property(property="role", type="string", enum={"user","agent"}, example="user"),
     *             @OA\Property(property="agent_data", type="object", description="Optional agent profile data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation Error")
     * )
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
                case 'private_person':
                    $role = 3; // Or use a specific agent role ID
                    break;
                case 'real_estate_agent':
                    $role = 4;
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
                'role' => $user->load('role')->role, // Explicitly load relationship
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
            \Illuminate\Support\Facades\Log::error('Registration Error: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="User Logout",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Logged out")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
