<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;
    protected $table = 'support_tickets';
    protected  $fillable = [
        'user_id',
        'ticket_id',
        'subject',
        'message',
        'category',
        'status',
        'priority',
        'chat',
        'file_attachment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
