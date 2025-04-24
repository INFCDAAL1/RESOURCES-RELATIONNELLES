<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Http\Requests\MessageRequest;
use App\Http\Resources\MessageResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Display a listing of the messages.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        // Get users with whom the current user has conversations
        $conversations = DB::table('messages')
            ->select('users.id', 'users.name', 'users.avatar', DB::raw('MAX(messages.created_at) as last_message_date'))
            ->join('users', function($join) use ($userId) {
                $join->on('users.id', '=', 'messages.sender_id')
                    ->where('messages.receiver_id', '=', $userId)
                    ->orWhere(function($query) use ($userId) {
                        $query->on('users.id', '=', 'messages.receiver_id')
                            ->where('messages.sender_id', '=', $userId);
                    });
            })
            ->where(function($query) use ($userId) {
                $query->where('messages.sender_id', $userId)
                    ->orWhere('messages.receiver_id', $userId);
            })
            ->where('users.id', '!=', $userId)
            ->groupBy('users.id', 'users.name', 'users.avatar')
            ->orderBy('last_message_date', 'desc')
            ->get();
            
        return response()->json($conversations);
    }
    
    /**
     * Get messages between current user and specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $receiverId
     * @return \Illuminate\Http\Response
     */
    public function getConversation(Request $request, $receiverId)
    {
        $userId = Auth::id();
        
        // Check if the receiver exists
        $receiver = User::find($receiverId);
        if (!$receiver) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Get the last 15 messages between the two users
        $messages = Message::where(function($query) use ($userId, $receiverId) {
                $query->where('sender_id', $userId)
                      ->where('receiver_id', $receiverId);
            })
            ->orWhere(function($query) use ($userId, $receiverId) {
                $query->where('sender_id', $receiverId)
                      ->where('receiver_id', $userId);
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get()
            ->reverse();
            
        // Mark received messages as read
        Message::where('sender_id', $receiverId)
            ->where('receiver_id', $userId)
            ->where('read', false)
            ->update(['read' => true]);
            
        return MessageResource::collection($messages);
    }

    /**
     * Store a newly created message.
     *
     * @param  \App\Http\Requests\MessageRequest  $request
     * @return \App\Http\Resources\MessageResource
     */
    public function store(MessageRequest $request)
    {
        $validated = $request->validated();
        
        $validated['sender_id'] = Auth::id();
        $validated['read'] = false;
        
        if (!isset($validated['receiver_id'])) {
            return response()->json([
                'message' => 'Receiver ID is required',
                'errors' => ['receiver_id' => ['The receiver field is required']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $message = Message::create($validated);
        
        return new MessageResource($message->load(['sender', 'receiver']));
    }

    /**
     * Update the message read status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Message  $message
     * @return \App\Http\Resources\MessageResource
     */
    public function update(Request $request, Message $message)
    {
        $user = Auth::user();
        
        // User can update if they're the receiver, or if they're admin/modo
        if ($message->receiver_id !== $user->id && 
            !$user->isAdmin() && 
            !$user->isModo()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $validated = $request->validate([
            'read' => 'required|boolean'
        ]);
        
        $message->update(['read' => $validated['read']]);
        
        return new MessageResource($message->load(['sender', 'receiver']));
    }

    /**
     * Remove the specified message.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function destroy(Message $message)
    {
        $user = Auth::user();
        
        // User can delete if they're sender/receiver, or if they're admin/modo
        if ($message->sender_id !== $user->id && 
            $message->receiver_id !== $user->id && 
            !$user->isAdmin() && 
            !$user->isModo()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $message->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}