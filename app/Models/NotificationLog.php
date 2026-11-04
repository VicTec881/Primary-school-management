<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'recipient_contact',
        'channel',
        'subject',
        'message',
        'status',
        'provider_response',
    ];
}

