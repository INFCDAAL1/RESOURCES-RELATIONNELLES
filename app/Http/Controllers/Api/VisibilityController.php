<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visibility;
use App\Http\Requests\VisibilityRequest;
use App\Http\Resources\VisibilityResource;
use Illuminate\Http\Response;

class VisibilityController extends Controller
{
    /**
     * Display a listing of the visibilities.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $visibilities = Visibility::all();
        return VisibilityResource::collection($visibilities);
    }

    /**
     * Store a newly created visibility in storage.
     *
     * @param  \App\Http\Requests\VisibilityRequest  $request
     * @return \App\Http\Resources\VisibilityResource
     */
    public function store(VisibilityRequest $request)
    {
        $visibility = Visibility::create($request->validated());
        return new VisibilityResource($visibility);
    }

    /**
     * Display the specified visibility.
     *
     * @param  \App\Models\Visibility  $visibility
     * @return \App\Http\Resources\VisibilityResource
     */
    public function show(Visibility $visibility)
    {
        return new VisibilityResource($visibility);
    }

    /**
     * Update the specified visibility in storage.
     *
     * @param  \App\Http\Requests\VisibilityRequest  $request
     * @param  \App\Models\Visibility  $visibility
     * @return \App\Http\Resources\VisibilityResource
     */
    public function update(VisibilityRequest $request, Visibility $visibility)
    {
        $visibility->update($request->validated());
        return new VisibilityResource($visibility);
    }

    /**
     * Remove the specified visibility from storage.
     *
     * @param  \App\Models\Visibility  $visibility
     * @return \Illuminate\Http\Response
     */
    public function destroy(Visibility $visibility)
    {
        // Check if there are resources using this visibility
        if ($visibility->resources()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this visibility because it is used by resources'
            ], Response::HTTP_CONFLICT); 
        }
        
        $visibility->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}