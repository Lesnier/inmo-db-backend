<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FavoriteController extends Controller
{
    // GET /api/user/favorites (auth)
    public function list(): JsonResponse
    {
        $user = auth()->user();
        $favorites = $user->favorite_properties()->paginate(10);
        return response()->json($favorites);
    }
}
