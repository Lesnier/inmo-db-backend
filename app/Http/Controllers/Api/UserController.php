<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user",
     *     summary="Get User Profile",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="User profile")
     * )
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user()->load(['role']);
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ? $user->role->name : 'user',
            'avatar' => $user->avatar, // Assuming Voyager uses avatar
            'settings' => $user->settings,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/user/profile",
     *     summary="Update User Profile",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated profile")
     * )
     */
    public function update(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
            'settings' => 'sometimes|array'
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
    
    // GET /api/user/analytics (stub)
    /**
     * @OA\Get(
     *     path="/api/user/analytics",
     *     summary="Get User Analytics",
     *     tags={"Users"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="User analytics")
     * )
     */
    public function analytics(): JsonResponse
    {
        $user = auth()->user();
        
        // Basic user stats
        return response()->json([
            'favorites_count' => $user->favorite_properties()->count(),
            'messages_sent' => \App\Models\Message::where('user_id', $user->id)->count(),
            'searches_saved' => 0, // Placeholder
            'last_active' => now()->subMinutes(rand(1, 100)), // Stub
        ]);
    }
}
