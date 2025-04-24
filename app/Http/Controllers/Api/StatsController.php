<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\User;
use App\Models\Category;
use App\Models\Message;
use App\Models\Comment;
use App\Models\Invitation;
use App\Models\ResourceInteraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Get general application statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function general(): JsonResponse
    {
        return response()->json([
            'users_count' => User::where('is_active', true)->count(),
            'resources_count' => Resource::where('published', true)->count(),
            'categories_count' => Category::count(),
            'messages_count' => Message::count(),
            'comments_count' => Comment::count(),
        ]);
    }

    /**
     * Get resource statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resources(): JsonResponse
    {
        return response()->json([
            'total' => Resource::count(),
            'published' => Resource::where('published', true)->count(),
            'validated' => Resource::where('validated', true)->count(),
            'by_category' => Category::withCount('resources')->get()->map(function ($category) {
                return [
                    'name' => $category->name,
                    'count' => $category->resources_count
                ];
            }),
            'recent_activity' => Resource::select(['id', 'name', 'created_at'])
                ->where('published', true)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * Get user engagement statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function engagement(): JsonResponse
    {
        return response()->json([
            'favorites_count' => ResourceInteraction::where('type', 'favorite')->count(),
            'saved_count' => ResourceInteraction::where('type', 'saved')->count(),
            'exploited_count' => ResourceInteraction::where('type', 'exploited')->count(),
            'comments_count' => Comment::count(),
            'invitations_count' => Invitation::count(),
            'invitations_by_status' => Invitation::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get(),
            'top_resources' => ResourceInteraction::select('resource_id', DB::raw('count(*) as interaction_count'))
                ->groupBy('resource_id')
                ->orderBy('interaction_count', 'desc')
                ->limit(5)
                ->with('resource:id,name')
                ->get()
                ->map(function ($interaction) {
                    return [
                        'resource_name' => $interaction->resource->name,
                        'interactions' => $interaction->interaction_count
                    ];
                }),
        ]);
    }

    /**
     * Get user activity statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function activity(): JsonResponse
    {
        $newUsersLastMonth = User::where('created_at', '>=', now()->subMonth())
            ->count();
            
        $resourcesAddedLastMonth = Resource::where('created_at', '>=', now()->subMonth())
            ->count();
            
        $messagesLastMonth = Message::where('created_at', '>=', now()->subMonth())
            ->count();
            
        return response()->json([
            'new_users_last_month' => $newUsersLastMonth,
            'resources_added_last_month' => $resourcesAddedLastMonth, 
            'messages_sent_last_month' => $messagesLastMonth,
            'recent_comments' => Comment::with('user:id,name', 'resource:id,name')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($comment) {
                    return [
                        'content' => \Str::limit($comment->content, 100),
                        'user' => $comment->user->name,
                        'resource' => $comment->resource->name,
                        'date' => $comment->created_at->format('Y-m-d H:i')
                    ];
                }),
            'most_active_users' => User::withCount(['resources', 'comments'])
                ->orderByRaw('resources_count + comments_count DESC')
                ->limit(5)
                ->get(['id', 'name', 'resources_count', 'comments_count']),
        ]);
    }
}