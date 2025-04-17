<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Http\Requests\InvitationRequest;
use App\Http\Resources\InvitationResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    /**
     * Display a listing of the invitations.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        // A user can see invitations they've sent or received
        $invitations = Invitation::where('sender_id', Auth::id())
            ->orWhere('receiver_id', Auth::id())
            ->with(['sender', 'receiver', 'resource'])
            ->get();
            
        return InvitationResource::collection($invitations);
    }

    /**
     * Store a newly created invitation in storage.
     *
     * @param  \App\Http\Requests\InvitationRequest  $request
     * @return \App\Http\Resources\InvitationResource
     */
    public function store(InvitationRequest $request)
    {
        $validated = $request->validated();
        
        // Set the sender as the current user
        $validated['sender_id'] = Auth::id();
        $validated['status'] = 'pending';
        
        $invitation = Invitation::create($validated);
        return new InvitationResource($invitation->load(['sender', 'receiver', 'resource']));
    }

    /**
     * Display the specified invitation.
     *
     * @param  \App\Models\Invitation  $invitation
     * @return \App\Http\Resources\InvitationResource
     */
    public function show(Invitation $invitation)
    {
        // Authorization: only sender or receiver can see the invitation
        if ($invitation->sender_id !== Auth::id() && $invitation->receiver_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        return new InvitationResource($invitation->load(['sender', 'receiver', 'resource']));
    }

    /**
     * Update the specified invitation in storage.
     *
     * @param  \App\Http\Requests\InvitationRequest  $request
     * @param  \App\Models\Invitation  $invitation
     * @return \App\Http\Resources\InvitationResource
     */
    public function update(InvitationRequest $request, Invitation $invitation)
    {
        // Authorization: only the receiver can update the invitation status
        if ($invitation->receiver_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        // Only allow updating the status
        $validated = $request->validated();
        
        $invitation->update(['status' => $validated['status']]);
        return new InvitationResource($invitation->load(['sender', 'receiver', 'resource']));
    }

    /**
     * Remove the specified invitation from storage.
     *
     * @param  \App\Models\Invitation  $invitation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invitation $invitation)
    {
        // Authorization: only sender can delete the invitation
        if ($invitation->sender_id !== Auth::id() && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $invitation->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}