<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Http\Requests\CommentRequest;
use App\Http\Resources\CommentResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Display a listing of the comments.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        // Get comments based on query parameters
        $query = Comment::query()->with(['user', 'resource']);
        
        // Filter by resource if specified
        if (request()->has('resource_id')) {
            $query->where('resource_id', request('resource_id'));
        }
        
        // Get only published comments unless admin or own comments
        if (!Auth::user()->isAdmin()) {
            $query->where(function($q) {
                $q->where('status', 'published')
                  ->orWhere('user_id', Auth::id());
            });
        }
        
        $comments = $query->latest()->paginate(10);
        
        return CommentResource::collection($comments);
    }

    /**
     * Store a newly created comment in storage.
     *
     * @param  \App\Http\Requests\CommentRequest  $request
     * @return \App\Http\Resources\CommentResource
     */
    public function store(CommentRequest $request)
    {
        $validated = $request->validated();
        
        // Set the user as the current user
        $validated['user_id'] = Auth::id();
        $validated['status'] = 'published'; // Default to published
        
        $comment = Comment::create($validated);
        return new CommentResource($comment->load(['user', 'resource']));
    }

    /**
     * Display the specified comment.
     *
     * @param  \App\Models\Comment  $comment
     * @return \App\Http\Resources\CommentResource
     */
    public function show(Comment $comment)
    {
        // Authorization: only visible if published or owner or admin
        if ($comment->status !== 'published' && 
            $comment->user_id !== Auth::id() && 
            !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        return new CommentResource($comment->load(['user', 'resource']));
    }

    /**
     * Update the specified comment in storage.
     *
     * @param  \App\Http\Requests\CommentRequest  $request
     * @param  \App\Models\Comment  $comment
     * @return \App\Http\Resources\CommentResource
     */
    public function update(CommentRequest $request, Comment $comment)
    {
        // Authorization: only owner or admin can update
        if ($comment->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $comment->update($request->validated());
        return new CommentResource($comment->load(['user', 'resource']));
    }

    /**
     * Remove the specified comment from storage.
     *
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Comment $comment)
    {
        // Authorization: only owner or admin can delete
        if ($comment->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $comment->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}