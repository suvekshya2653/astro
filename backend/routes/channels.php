<?php

use Illuminate\Support\Facades\Broadcast;



Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    return true; // Public chat between admin & customer
});
