<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageSent; // 👈 add this line


class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return Message::where('user_id', $user->id)->get();
    }



    public function store(Request $request)
    {
        $validated = $request->validate([
        'text' => 'required|string',
    ]);

        $user = $request->user();

        $message = Message::create([
        'user_id' => $user->id,
        'text' => $validated['text'],
    ]);

    broadcast(new MessageSent($message))->toOthers();

    return response()->json($message, 201);
}

}
