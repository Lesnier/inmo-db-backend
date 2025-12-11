<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/chat/rooms",
     *      tags={"Chat"},
     *      summary="List user's chat rooms",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200, 
     *          description="List of chats",
     *          @OA\JsonContent(type="array", @OA\Items(type="object"))
     *      )
     * )
     */
    public function index()
    {
        $user = Auth::user();
        
        $chats = Chat::whereHas('participants', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->with(['participants', 'latestMessage'])
        ->withCount(['messages as unread_count' => function($query) use ($user) {
             // Logic for unread: messages created after my last_read_at
             // This is complex in a single query without joining participants table again
             // Simplified: just return raw count for now or 0
             $query->where('created_at', '>', DB::raw('NOW()')); // Placeholder
        }])
        ->orderBy('updated_at', 'desc')
        ->paginate(20);

        return response()->json($chats);
    }

    /**
     * @OA\Post(
     *      path="/api/chat/rooms",
     *      tags={"Chat"},
     *      summary="Create or get existing private chat",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"participant_id"},
     *              @OA\Property(property="participant_id", type="integer", example=2),
     *              @OA\Property(property="subject", type="string", example="Project A")
     *          )
     *      ),
     *      @OA\Response(response=201, description="Chat created", @OA\JsonContent(type="object"))
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'participant_id' => 'required|exists:users,id',
            'subject' => 'nullable|string'
        ]);

        $me = Auth::id();
        $otherBy = $request->participant_id;

        // Check if private chat already exists
        // simplified logic: find chat with exactly these 2 participants and type='private'
        // This is tricky in Eloquent, so multiple steps:
        
        $chat = Chat::where('type', 'private')
            ->whereHas('participants', function($q) use ($me) { $q->where('user_id', $me); })
            ->whereHas('participants', function($q) use ($otherBy) { $q->where('user_id', $otherBy); })
            ->first();

        if (!$chat) {
            $chat = Chat::create([
                'type' => 'private',
                'subject' => $request->subject
            ]);

            $chat->participants()->attach([$me, $otherBy]);
        }

        return response()->json($chat->load('participants'), 201);
    }

    /**
     * @OA\Get(
     *      path="/api/chat/rooms/{id}/messages",
     *      tags={"Chat"},
     *      summary="Get messages for a room",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200, 
     *          description="List of messages",
     *          @OA\JsonContent(type="array", @OA\Items(type="object"))
     *      )
     * )
     */
    public function messages($id)
    {
        $chat = Chat::findOrFail($id);
        
        // Authorization check
        if (!$chat->participants->contains(Auth::id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = $chat->messages()->with('sender')->latest()->paginate(50);
        
        // Mark as read
        // Update pivot last_read_at
        $chat->participants()->updateExistingPivot(Auth::id(), ['last_read_at' => now()]);

        return response()->json($messages);
    }

    /**
     * @OA\Post(
     *      path="/api/chat/rooms/{id}/messages",
     *      tags={"Chat"},
     *      summary="Send a message",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"content"},
     *              @OA\Property(property="content", type="string", example="Hello world")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201, 
     *          description="Message sent",
     *          @OA\JsonContent(type="object")
     *      )
     * )
     */
    public function sendMessage(Request $request, $id)
    {
        $chat = Chat::findOrFail($id);
        
        if (!$chat->participants->contains(Auth::id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate(['content' => 'required|string']);

        $message = $chat->messages()->create([
            'user_id' => Auth::id(),
            'content' => $request->input('content'),
            'type' => 'text'
        ]);
        
        $chat->touch(); // Update updated_at of chat to bubble it to top

        // Trigger Broadcast
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json($message->load('sender'), 201);
    }
}
