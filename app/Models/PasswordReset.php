<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'email',
        'token',
        'otp',
        'expires_at'
    ];

    public $table = 'password_reset_tokens';
}
