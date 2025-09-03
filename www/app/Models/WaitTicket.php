<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitTicket extends Model {
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id','reservation_id','token_jwt','otp_code','otp_expires_at'];
    protected $casts = ['otp_expires_at' => 'datetime'];

}
