<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(ChatMessage $message)
    {
        $this->message = $message->load('admin');
    }

    public function broadcastOn()
    {
        return new Channel('chat.' . $this->message->conversation_id);
    }

    public function broadcastAs()
    {
        return 'NewChatMessage';
    }

    public function broadcastWith()
    {
        return [
            'id'            => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'message'       => $this->message->message,
            'sender_type'   => $this->message->sender_type,
            'sender_id'     => $this->message->sender_id,
            'admin'         => $this->message->admin?->name,
            'created_at'    => $this->message->created_at->toDateTimeString()
        ];
    }
}
