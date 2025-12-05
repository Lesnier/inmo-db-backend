<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * GET /api/user (auth)
     */
    public function profile(): JsonResponse
    {
        return response()->json([
            'id' => auth()->id(),
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'role' => auth()->user()->role ?? 'user',
        ]);
    }
}
