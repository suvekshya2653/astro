<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminChatNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'admin_id',
        'note',
    ];
}
