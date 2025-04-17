<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Type;
use App\Http\Requests\TypeRequest;
use App\Http\Resources\TypeResource;
use Illuminate\Http\Response;

class TypeController extends Controller
{
    /**
     * Display a listing of the types.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $types = Type::all();
        return TypeResource::collection($types);
    }

    /**
     * Store a newly created type in storage.
     *
     * @param  \App\Http\Requests\TypeRequest  $request
     * @return \App\Http\Resources\TypeResource
     */
    public function store(TypeRequest $request)
    {
        $type = Type::create($request->validated());
        return new TypeResource($type);
    }

    /**
     * Display the specified type.
     *
     * @param  \App\Models\Type  $type
     * @return \App\Http\Resources\TypeResource
     */
    public function show(Type $type)
    {
        return new TypeResource($type);
    }

    /**
     * Update the specified type in storage.
     *
     * @param  \App\Http\Requests\TypeRequest  $request
     * @param  \App\Models\Type  $type
     * @return \App\Http\Resources\TypeResource
     */
    public function update(TypeRequest $request, Type $type)
    {
        $type->update($request->validated());
        return new TypeResource($type);
    }

    /**
     * Remove the specified type from storage.
     *
     * @param  \App\Models\Type  $type
     * @return \Illuminate\Http\Response
     */
    public function destroy(Type $type)
    {
        // Check if the type is being used by any resources
        if ($type->resources()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete this type as it is being used by resources'
            ], Response::HTTP_CONFLICT);
        }

        $type->delete();
        return response()->json(['message' => 'Type deleted successfully']);
    }
}