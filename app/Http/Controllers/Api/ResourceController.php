<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResourceRequest;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ResourceController extends Controller
{
    /**
     * Display a listing of the resources.
     */
    public function index()
    {
        $resources = Resource::with(['type', 'category', 'visibility', 'user', 'origin'])
            ->when(!Auth::user()->isAdmin(), function ($query) {
                // Non-admin users can only see published and validated resources
                // or resources they own
                return $query->where(function ($q) {
                    $q->where('published', true)
                      ->where('validated', true)
                      ->orWhere('user_id', Auth::id());
                });
            })
            ->paginate(10);

        return ResourceResource::collection($resources);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ResourceRequest $request)
    {
        $validated = $request->validated();
        
        // Remove file from validated data to handle it separately
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            unset($validated['file']);
        }
        
        // Create resource
        $resource = new Resource($validated);
        $resource->user_id = Auth::id();
        $resource->save();
        
        // Handle file upload if present
        if (isset($file)) {
            $resource->uploadFile($file);
        }
        
        return new ResourceResource($resource->load(['type', 'category', 'visibility', 'user', 'origin']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Resource $resource)
    {
        // Check if user can view this resource
        if (!Auth::user()->isAdmin() && 
            $resource->user_id !== Auth::id() && 
            (!$resource->published || !$resource->validated)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return new ResourceResource($resource->load(['type', 'category', 'visibility', 'user', 'origin']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ResourceRequest $request, Resource $resource)
    {
        $validated = $request->validated();
        
        // Remove file from validated data to handle it separately
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            unset($validated['file']);
        }
        
        // Update resource
        $resource->update($validated);
        
        // Handle file upload if present
        if (isset($file)) {
            $resource->uploadFile($file);
        }
        
        return new ResourceResource($resource->load(['type', 'category', 'visibility', 'user', 'origin']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource)
    {
        // Delete associated file
        $resource->deleteFile();
        
        // Delete the resource itself
        $resource->delete();
        
        return response()->json(['message' => 'Resource deleted successfully']);
    }
    
    /**
     * Download the resource file.
     */
    public function download(Resource $resource)
    {
        // Check if user can download this resource
        if (!Auth::user()->isAdmin() && 
            $resource->user_id !== Auth::id() && 
            (!$resource->published || !$resource->validated)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if (!$resource->file_path || !Storage::exists($resource->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }
        
        return Storage::download(
            $resource->file_path, 
            $resource->name . '.' . pathinfo($resource->file_path, PATHINFO_EXTENSION)
        );
    }
}