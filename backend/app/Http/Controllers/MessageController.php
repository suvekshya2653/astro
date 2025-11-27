<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Get messages between current user and another user
     * Used by both Admin and Customer
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Get receiver_id from query params
        $receiverId = $request->query('receiver_id');

        if (!$receiverId) {
            return response()->json(['message' => 'receiver_id is required'], 400);
        }

        Log::info("Fetching messages between User {$user->id} and User {$receiverId}");

        // Get all messages between these two users (bidirectional)
        $messages = Message::where(function($query) use ($user, $receiverId) {
            $query->where('user_id', $user->id)
                  ->where('receiver_id', $receiverId);
        })
        ->orWhere(function($query) use ($user, $receiverId) {
            $query->where('user_id', $receiverId)
                  ->where('receiver_id', $user->id);
        })
        ->with('user:id,name') // Eager load sender info
        ->orderBy('created_at', 'asc')
        ->get()
        ->map(function($message) {
            return [
                'id' => $message->id,
                'text' => $message->text,
                'user_id' => $message->user_id,
                'receiver_id' => $message->receiver_id,
                'created_at' => $message->created_at->toISOString(),
                'updated_at' => $message->updated_at->toISOString(),
                'sender_name' => $message->user->name ?? 'Unknown',
            ];
        });

        return response()->json($messages);
    }

    /**
     * Send a new message
     * Broadcasts to real-time channel
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'receiver_id' => 'required|exists:users,id',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        Log::info("User {$user->id} sending message to User {$validated['receiver_id']}");

        // Create message
        $message = Message::create([
            'user_id' => $user->id,
            'receiver_id' => $validated['receiver_id'],
            'text' => $validated['text'],
        ]);

        // Load sender relationship
        $message->load('user:id,name');

        // Broadcast to real-time channel
        try {
            broadcast(new MessageSent($message))->toOthers();
            Log::info("Message {$message->id} broadcast successfully");
        } catch (\Exception $e) {
            Log::error("Broadcast failed: " . $e->getMessage());
        }

        // Return formatted response
        return response()->json([
            'id' => $message->id,
            'text' => $message->text,
            'user_id' => $message->user_id,
            'receiver_id' => $message->receiver_id,
            'created_at' => $message->created_at->toISOString(),
            'updated_at' => $message->updated_at->toISOString(),
            'sender_name' => $message->user->name ?? 'Unknown',
        ], 201);
    }

    /**
     * Get all chat users for admin dashboard
     * Returns list of customers with their last message
     */
    public function chatUsers(Request $request)
    {
        $currentUser = $request->user();

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Log::info("Fetching chat users for User {$currentUser->id}");

        // Get all users who have exchanged messages with current user
        $userIds = Message::where('user_id', $currentUser->id)
            ->orWhere('receiver_id', $currentUser->id)
            ->pluck('user_id')
            ->merge(
                Message::where('user_id', $currentUser->id)
                    ->orWhere('receiver_id', $currentUser->id)
                    ->pluck('receiver_id')
            )
            ->unique()
            ->reject(fn($id) => $id == $currentUser->id); // Remove self

        // Get user details with last message
        $users = User::whereIn('id', $userIds)
            ->get()
            ->map(function($user) use ($currentUser) {
                // Get last message between current user and this user
                $lastMessage = Message::where(function($query) use ($currentUser, $user) {
                    $query->where('user_id', $currentUser->id)
                          ->where('receiver_id', $user->id);
                })
                ->orWhere(function($query) use ($currentUser, $user) {
                    $query->where('user_id', $user->id)
                          ->where('receiver_id', $currentUser->id);
                })
                ->latest()
                ->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'dob_nep' => $user->dob_nep ?? null,
                    'birth_time' => $user->birth_time ?? null,
                    'birth_place' => $user->birth_place ?? null,
                    'temp_address' => $user->temp_address ?? null,
                    'last_message' => $lastMessage ? [
                        'id' => $lastMessage->id,
                        'text' => $lastMessage->text,
                        'user_id' => $lastMessage->user_id,
                        'receiver_id' => $lastMessage->receiver_id,
                        'created_at' => $lastMessage->created_at->toISOString(),
                    ] : null,
                ];
            })
            ->sortByDesc(function($user) {
                return $user['last_message']['created_at'] ?? '1970-01-01';
            })
            ->values();

        return response()->json($users);
    }

    /**
     * Get all users (for starting new conversations)
     * Optional: Used if you want to show all available users
     */
    public function allUsers(Request $request)
    {
        $currentUser = $request->user();

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $users = User::where('id', '!=', $currentUser->id)
            ->select(
                'id',
                'name',
                'email',
                'dob_nep',
                'birth_time',
                'birth_place',
                'temp_address'
            )
            ->get();

        return response()->json($users);
    }

    /**
     * Mark messages as read (optional feature)
     */
    public function markAsRead(Request $request)
    {
        $validated = $request->validate([
            'sender_id' => 'required|exists:users,id',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Mark all messages from sender to current user as read
        Message::where('user_id', $validated['sender_id'])
               ->where('receiver_id', $user->id)
               ->where('is_read', false)
               ->update(['is_read' => true]);

        return response()->json(['message' => 'Messages marked as read']);
    }
}
