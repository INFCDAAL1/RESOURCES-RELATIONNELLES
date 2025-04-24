<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get a simplified list of all users (id and name only).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::select(['id', 'name'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $users
        ]);
    }

    /**
     * Search users by name.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query');
        
        $users = User::select(['id', 'name'])
            ->where('is_active', true)
            ->where('name', 'LIKE', "%{$query}%")
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json([
            'users' => $users
        ]);
    }
}