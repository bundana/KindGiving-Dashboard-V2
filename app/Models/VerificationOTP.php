<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationOTP extends Model
{
    use HasFactory;
    public $table = 'verification_otps';
    protected $fillable = [
        'user_id',
        'otp', 
        'token',
        'expires_at'
    ];
}
