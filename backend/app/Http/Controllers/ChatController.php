<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Events\NewChatMessage;
use App\Models\User;

class ChatController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER SEND MESSAGE
    |--------------------------------------------------------------------------
    */
    public function customerSend(Request $request)
    {
        $request->validate([
            'message'         => 'required|string',
            'conversation_id' => 'nullable|integer',
            'name'            => 'required|string',
            'email'           => 'required|email',
        ]);

        // Create NEW conversation
        if (!$request->conversation_id) {

            $conversation = Conversation::create([
                'customer_id'      => null,
                'customer_name'    => $request->name,
                'customer_email'   => $request->email,
                'last_message_at'  => now()
            ]);

        } else {
            $conversation = Conversation::find($request->conversation_id);

            if (!$conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found.'
                ], 404);
            }
        }

        // Save customer message
        $msg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'customer',
            'sender_id'       => null,
            'message'         => $request->message,
            'is_read'         => 0,
        ]);

        $conversation->update(['last_message_at' => now()]);

        // 🔥 Broadcast to ADMIN panel
        broadcast(new NewChatMessage($msg))->toOthers();

        return response()->json([
            'success'         => true,
            'conversation_id' => $conversation->id,
            'message'         => $msg
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | ADMIN REPLY
    |--------------------------------------------------------------------------
    */
    public function adminReply(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|integer',
            'message'         => 'required|string',
        ]);

        $conversation = Conversation::find($request->conversation_id);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found.'
            ], 404);
        }

        $admin = auth('sanctum')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin not authenticated.',
            ], 401);
        }

        // Save admin message
        $msg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => 'admin',
            'sender_id'       => $admin->id,
            'message'         => $request->message,
            'is_read'         => 0,
        ]);

        $conversation->update(['last_message_at' => now()]);

        // 🔥 Broadcast to CUSTOMER
        broadcast(new NewChatMessage($msg))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | CHAT HISTORY
    |--------------------------------------------------------------------------
    */
    public function history($conversationId)
    {
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found.'
            ], 404);
        }

        $messages = ChatMessage::where('conversation_id', $conversationId)
            ->orderBy('id', 'ASC')
            ->get();

        return response()->json([
            'success'  => true,
            'conversation' => [
                'id'    => $conversation->id,
                'name'  => $conversation->customer_name,
                'email' => $conversation->customer_email,
            ],
            'messages' => $messages
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | ADMIN PANEL – FETCH ALL CHAT USERS
    |--------------------------------------------------------------------------
    */
    public function allCustomers()
    {
        $conversations = Conversation::with(['messages' => function ($q) {
            $q->latest()->limit(1);
        }])
        ->orderBy('last_message_at', 'DESC')
        ->get()
        ->map(function ($conv) {
            return [
                'id'            => $conv->id,
                'name'          => $conv->customer_name ?? 'Guest User',
                'email'         => $conv->customer_email ?? 'guest@example.com',
                'last_message'  => $conv->messages->first()->message ?? '',
                'last_time'     => $conv->last_message_at
            ];
        });

        return response()->json($conversations);
    }

}
