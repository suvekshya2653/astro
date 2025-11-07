<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class MessageSent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message->load('user'); // include user relationship
    }

    /**
     * The channel the event should broadcast on.
     */
    public function broadcastOn()
    {
        // public channel "chat" — matches echo.channel("chat") in frontend
        return new Channel('chat');
    }

    /**
     * The event name listened to by the frontend.
     */
    public function broadcastAs()
    {
        return 'MessageSent';
    }

    /**
     * The data sent to the frontend.
     */
    public function broadcastWith()
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'text' => $this->message->text,
                'user' => [
                    'id' => $this->message->user->id,
                    'name' => $this->message->user->name,
                ],
                'created_at' => $this->message->created_at,
            ],
        ];
    }
}
