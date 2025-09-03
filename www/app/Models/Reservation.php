<?php
// app/Models/Reservation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


// app/Models/Reservation.php
class Reservation extends Model {
    use HasFactory;
    public $incrementing = false;   // UUID 主キー
    protected $keyType = 'string';
    protected $fillable = [
        'id','tenant_id','customer_user_id',
        'start_at','end_at','scheduled_at',
        'amount','price_jpy','stripe_payment_intent_id',
        'room_name','status','host_code','guest_code',
        'host_name','guest_name','guest_email',
        'purpose_title','purpose_detail',
        'requester_name','requester_email','requester_phone',
    ];
    protected $appends  = ['host_url','guest_url'];
    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
        'scheduled_at' => 'datetime',
    ];
    public function getHostUrlAttribute(): string {
        return frontend_origin().'/join/host/'.$this->host_code;
    }
    public function getGuestUrlAttribute(): string {
        return frontend_origin().'/join/guest/'.$this->guest_code;
    }
}
