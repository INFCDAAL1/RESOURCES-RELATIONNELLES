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

class MessageController extends Controller
{
    /**
     * Display a listing of the messages.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Message::query();
        
        // Filter: conversation with specific user
        if ($request->has('user_id')) {
            $otherUserId = $request->input('user_id');
            
            $query->where(function($q) use ($otherUserId) {
                $q->where(function($inner) use ($otherUserId) {
                    $inner->where('sender_id', Auth::id())
                          ->where('receiver_id', $otherUserId);
                })->orWhere(function($inner) use ($otherUserId) {
                    $inner->where('sender_id', $otherUserId)
                          ->where('receiver_id', Auth::id());
                });
            });
        } else {
            // Default: messages sent or received by current user
            $query->where(function($q) {
                $q->where('sender_id', Auth::id())
                  ->orWhere('receiver_id', Auth::id());
            });
        }
        
        // Include relationships
        $query->with(['sender', 'receiver']);
        
        // Sort by newest first
        $query->latest();
        
        $messages = $query->paginate(15);
        
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
        
        // Set sender to current authenticated user
        $validated['sender_id'] = Auth::id();
        $validated['read'] = false;
        
        $message = Message::create($validated);
        
        return new MessageResource($message->load(['sender', 'receiver']));
    }

    /**
     * Display the specified message.
     *
     * @param  \App\Models\Message  $message
     * @return \App\Http\Resources\MessageResource
     */
    public function show(Message $message)
    {
        // Authorization: only sender or receiver can see the message
        if ($message->sender_id !== Auth::id() && $message->receiver_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        // Mark as read if viewing as receiver
        if ($message->receiver_id === Auth::id() && !$message->read) {
            $message->update(['read' => true]);
        }
        
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
        // Only receiver can mark as read/unread
        if ($message->receiver_id !== Auth::id()) {
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
        // Authorization: only sender or receiver or admin can delete the message
        if ($message->sender_id !== Auth::id() && 
            $message->receiver_id !== Auth::id() && 
            !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }
        
        $message->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
    
    /**
     * Get conversations (list of users the current user has exchanged messages with).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function conversations()
    {
        $sentToUsers = Message::where('sender_id', Auth::id())
                        ->select('receiver_id as user_id')
                        ->distinct();
                        
        $receivedFromUsers = Message::where('receiver_id', Auth::id())
                            ->select('sender_id as user_id')
                            ->distinct();
        
        $userIds = $sentToUsers->union($receivedFromUsers)->pluck('user_id');
        
        $users = User::whereIn('id', $userIds)->get();
        
        $conversations = $users->map(function($user) {
            $lastMessage = Message::where(function($query) use ($user) {
                    $query->where(function($q) use ($user) {
                        $q->where('sender_id', Auth::id())
                        ->where('receiver_id', $user->id);
                    })->orWhere(function($q) use ($user) {
                        $q->where('sender_id', $user->id)
                        ->where('receiver_id', Auth::id());
                    });
                })
                ->with('sender')
                ->latest()  
                ->first(); 
            
            $unreadCount = Message::where('sender_id', $user->id)
                            ->where('receiver_id', Auth::id())
                            ->where('read', false)
                            ->count();
            
            $formattedMessage = null;
            if ($lastMessage) {
                $formattedMessage = [
                    'id' => $lastMessage->id,
                    'content' => $lastMessage->content,
                    'read' => $lastMessage->read,
                    'sender' => [
                        'id' => $lastMessage->sender->id,
                        'name' => $lastMessage->sender->name
                    ],
                    'created_at' => $lastMessage->created_at,
                    'updated_at' => $lastMessage->updated_at
                ];
            }
            
            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name
                ],
                'last_message' => $formattedMessage,
                'unread_count' => $unreadCount,
                'last_activity' => $lastMessage ? $lastMessage->created_at : null
            ];
        })
        ->sortByDesc('last_activity')  
        ->values();
        
        return response()->json([
            'conversations' => $conversations
        ]);
    }
    
    /**
     * Mark all messages from a specific sender as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead(Request $request)
    {
        $validated = $request->validate([
            'sender_id' => 'required|exists:users,id'
        ]);
        
        $count = Message::where('sender_id', $validated['sender_id'])
                  ->where('receiver_id', Auth::id())
                  ->where('read', false)
                  ->update(['read' => true]);
        
        return response()->json([
            'message' => "{$count} messages marked as read",
            'updated_count' => $count
        ]);
    }
}