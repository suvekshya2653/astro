<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
    'customer_id',
    'customer_name',
    'customer_email',
    'is_closed',
    'last_message_at'
    ];


    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP: Conversation → Customer (User)
    |--------------------------------------------------------------------------
    */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP: Conversation → Messages
    |--------------------------------------------------------------------------
    */
    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}
