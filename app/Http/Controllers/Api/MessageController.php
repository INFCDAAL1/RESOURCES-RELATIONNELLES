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
     * Display a listing of conversations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Get users with whom the current user has conversations
        $userList = DB::table('messages')
            ->select(
                'users.id',
                'users.name'
            )
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
            ->groupBy('users.id', 'users.name')
            ->get();

        // Now enrich each conversation with the last 5 messages
        $conversations = [];
        foreach ($userList as $user) {
            // Get the last 5 messages
            $lastMessages = Message::with('sender')
                ->where(function($query) use ($userId, $user) {
                    $query->where('sender_id', $userId)
                          ->where('receiver_id', $user->id);
                })
                ->orWhere(function($query) use ($userId, $user) {
                    $query->where('sender_id', $user->id)
                          ->where('receiver_id', $userId);
                })
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function($message) {
                    return [
                        'id' => $message->id,
                        'sender' => [
                            'id' => $message->sender->id,
                            'name' => $message->sender->name
                        ],
                        'created_at' => $message->created_at,
                        'content' => $message->content
                    ];
                });

            // Add to result array with last_message as the last 5 messages
            $conversations[] = [
                'id' => $user->id,
                'name' => $user->name,
                'last_message' => $lastMessages
            ];
        }

        // Sort by the created_at of the first message in last_message array
        usort($conversations, function($a, $b) {
            $aTime = isset($a['last_message'][0]) ? strtotime($a['last_message'][0]['created_at']) : 0;
            $bTime = isset($b['last_message'][0]) ? strtotime($b['last_message'][0]['created_at']) : 0;
            return $bTime - $aTime;
        });

        return response()->json(["data" => $conversations]);
    }

    /**
     * Get messages between current user and specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $receiverId
     * @return \Illuminate\Http\Response
     */
    public function getConversation(Request $request, $interlocutorId)
    {
        $userId = Auth::id();

        // Check if the receiver exists
        $interlocutor = User::find($interlocutorId);
        if (!$interlocutor) {
            return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Get all messages between the two users
        $messages = Message::where(function($query) use ($userId, $interlocutorId) {
                $query->where('sender_id', $userId)
                      ->where('receiver_id', $interlocutorId);
            })
            ->orWhere(function($query) use ($userId, $interlocutorId) {
                $query->where('sender_id', $interlocutorId)
                      ->where('receiver_id', $userId);
            })
            ->with('sender')
            ->orderBy('created_at', 'asc')  // Messages par ordre chronologique
            ->get()
            ->map(function($message) {
                return [
                    'id' => $message->id,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name
                    ],
                    'created_at' => $message->created_at,
                    'content' => $message->content
                ];
            });

        // Mark received messages as read
        Message::where('sender_id', $interlocutorId)
            ->where('receiver_id', $userId)
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json(["data" => $messages]);
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
