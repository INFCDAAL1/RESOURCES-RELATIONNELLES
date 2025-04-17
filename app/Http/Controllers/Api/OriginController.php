<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Origin;
use App\Http\Requests\OriginRequest;
use App\Http\Resources\OriginResource;
use Illuminate\Http\Response;

class OriginController extends Controller
{
    /**
     * Display a listing of the origins.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $origins = Origin::all();
        return OriginResource::collection($origins);
    }

    /**
     * Store a newly created origin in storage.
     *
     * @param  \App\Http\Requests\OriginRequest  $request
     * @return \App\Http\Resources\OriginResource
     */
    public function store(OriginRequest $request)
    {
        $origin = Origin::create($request->validated());
        return new OriginResource($origin);
    }

    /**
     * Display the specified origin.
     *
     * @param  \App\Models\Origin  $origin
     * @return \App\Http\Resources\OriginResource
     */
    public function show(Origin $origin)
    {
        return new OriginResource($origin);
    }

    /**
     * Update the specified origin in storage.
     *
     * @param  \App\Http\Requests\OriginRequest  $request
     * @param  \App\Models\Origin  $origin
     * @return \App\Http\Resources\OriginResource
     */
    public function update(OriginRequest $request, Origin $origin)
    {
        $origin->update($request->validated());
        return new OriginResource($origin);
    }

    /**
     * Remove the specified origin from storage.
     *
     * @param  \App\Models\Origin  $origin
     * @return \Illuminate\Http\Response
     */
    public function destroy(Origin $origin)
    {
        // Check if there are resources using this origin
        if ($origin->resources()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this origin because it is used by resources'
            ], Response::HTTP_CONFLICT); 
        }
        
        $origin->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}