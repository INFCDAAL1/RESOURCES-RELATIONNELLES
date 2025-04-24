<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResourceRequest;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Invitation;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;


class ResourceController extends Controller
{
    /**
     * Display a listing of the resources.
     */
    public function index(Request $request)
    {
        return $this->getAuthorizedResources($request);
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
        if(!$this->canRead($resource)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new ResourceResource($resource->load(['type', 'category', 'visibility', 'user', 'origin']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ResourceRequest $request, Resource $resource)
    {
        if(!$this->canEdit($resource)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        $validated = $request->validated();

        // Update resource
        $resource->update($validated);

        return new ResourceResource($resource->load(['type', 'category', 'visibility', 'user', 'origin']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resource $resource)
    {
        if(!$this->canEdit($resource)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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
        if(!$this->canRead($resource)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$resource->file_path || !Storage::exists($resource->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $name = $resource->name . '.' . pathinfo($resource->file_path, PATHINFO_EXTENSION);
        return Storage::download(
            $resource->file_path,
            $name
        );
    }

    /**
     * Favorite a resource.
     */
    public function favorite(Request $request, Resource $resource)
    {
        if(!$this->canRead($resource)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'setTo' => 'required|boolean',
        ]);

        $user = Auth::user();

        if ($validated['setTo']) {
            // Add to favorites
            $user->addFavorite($resource);
        } else {
            // Remove from favorites
            $user->removeFavorite($resource);
        }

        return response()->json([
            'message' => $validated['setTo'] ? 'Resource added to favorites' : 'Resource removed from favorites'
        ]);
    }

    public function getAuthorizedResources(Request $request)
    {
        $user = Auth::user();

        $resources = Resource::with(['category', 'visibility', 'user', 'type'])
            ->where('user_id', $user->id ?? null)
            ->orWhere(function ($query) use ($user) {
                $query
                    ->where('published', true)
                    ->where('validated', true)
                    ->where(function ($query) use ($user) {
                        $query
                            ->where('visibility_id', 1)
                            ->orWhere(function ($query) use ($user) {
                                $query
                                    ->where('visibility_id', 3)
                                    ->whereHas('invitations', function ($query) use ($user) {
                                        $query->where('receiver_id', $user->id ?? null)
                                            ->where('status', 'accepted');
                                    });
                            });
                    });
            });

        if($user && ($user->isAdmin() || $user->isModo())) {
            $resources = $resources->orWhere('published', true);
        }

        $resources = $resources
            ->latest()
            ->get();

        return ResourceResource::collection($resources);
    }

    public function validateResource(Request $request, Resource $resource)
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isModo())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'setTo' => 'required|boolean',
        ]);

        if ($validated['setTo']) {
            $resource->validated = true;
        } else {
            $resource->validated = false;
        }
        $resource->save();

        return new ResourceResource($resource->load(['type', 'category', 'visibility', 'user', 'origin']));
    }

    public function canRead(Resource $resource)
    {
        $user = Auth::user();
        if ($user->isAdmin() || $user->isModo()) return true;
        else if ($resource->user_id === $user->id) return true;
        else if ($resource->published && $resource->validated) {
            if($resource->visibility->name === 'public') return true;
            else if ($resource->visibility->name === 'private') return false;
            else if ($resource->visibility->name === 'restricted') {
                // Check if the user has accepted the invitation
                return $this->hasAcceptedInvitation($user, $resource);
            }
        }

        return false;
    }

    public function canEdit(Resource $resource)
    {
        $user = Auth::user();
        if ($user->isAdmin()) return true;
        else if ($resource->user_id === $user->id) return true;

        return false;
    }

    public function hasAcceptedInvitation(User $user, Resource $resource)
    {
        return $user->invitations()
            ->where('resource_id', $resource->id)
            ->where('status', 'accepted')
            ->exists();
    }
}
