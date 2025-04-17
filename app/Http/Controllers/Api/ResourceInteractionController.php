<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResourceInteraction;
use App\Http\Requests\ResourceInteractionRequest;
use App\Http\Resources\ResourceInteractionResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ResourceInteractionController extends Controller
{
    /**
     * Display a listing of the resource interactions.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        // Get interactions based on query parameters
        $query = ResourceInteraction::query()->with(['user', 'resource']);
        
        // Filter by resource if specified
        if (request()->has('resource_id')) {
            $query->where('resource_id', request('resource_id'));
        }
        
        // Filter by type if specified
        if (request()->has('type')) {
            $query->where('type', request('type'));
        }
        
        // Users can only see their own interactions unless admin
        if (!Auth::user()->isAdmin()) {
            $query->where('user_id', Auth::id());
        }
        
        $interactions = $query->latest()->paginate(10);
        
        return ResourceInteractionResource::collection($interactions);
    }

    /**
     * Store a newly created resource interaction in storage.
     *
     * @param  \App\Http\Requests\ResourceInteractionRequest  $request
     * @return \App\Http\Resources\ResourceInteractionResource
     */
    public function store(ResourceInteractionRequest $request)
    {
        $validated = $request->validated();
        
        // Set the user as the current user
        $validated['user_id'] = Auth::id();
        
        // Check if interaction already exists
        $existingInteraction = ResourceInteraction::where('user_id', Auth::id())
                                  ->where('resource_id', $validated['resource_id'])
                                  ->where('type', $validated['type'])
                                  ->first();

        if ($existingInteraction) {
            // Update existing interaction if it exists
            $existingInteraction->update($validated);
            $interaction = $existingInteraction;
        } else {
            // Create new interaction
            $interaction = ResourceInteraction::create($validated);
        }
        
        return new ResourceInteractionResource($interaction->load(['user', 'resource']));
    }

    /**
     * Display the specified resource interaction.
     *
     * @param  \App\Models\ResourceInteraction  $resourceInteraction
     * @return \App\Http\Resources\ResourceInteractionResource
     */
    public function show(ResourceInteraction $resourceInteraction)
    {
        // Authorization: only owner or admin can see
        if ($resourceInteraction->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        return new ResourceInteractionResource($resourceInteraction->load(['user', 'resource']));
    }

    /**
     * Update the specified resource interaction in storage.
     *
     * @param  \App\Http\Requests\ResourceInteractionRequest  $request
     * @param  \App\Models\ResourceInteraction  $resourceInteraction
     * @return \App\Http\Resources\ResourceInteractionResource
     */
    public function update(ResourceInteractionRequest $request, ResourceInteraction $resourceInteraction)
    {
        // Authorization: only owner can update
        if ($resourceInteraction->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $resourceInteraction->update($request->validated());
        return new ResourceInteractionResource($resourceInteraction->load(['user', 'resource']));
    }

    /**
     * Remove the specified resource interaction from storage.
     *
     * @param  \App\Models\ResourceInteraction  $resourceInteraction
     * @return \Illuminate\Http\Response
     */
    public function destroy(ResourceInteraction $resourceInteraction)
    {
        // Authorization: only owner or admin can delete
        if ($resourceInteraction->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $resourceInteraction->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}