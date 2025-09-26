<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallLog extends Model {

    protected $table = 'call_logs';
    protected $fillable = [
        'appointment_id','room_name','host_user_id','guest_user_id',
        'started_at','ended_at','duration_sec','outcome','summary',
        'consultation_fee','checkout_session_id','meta'
    ];
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'meta'       => 'array',
    ];
    public function appointment(){ return $this->belongsTo(Appointment::class); }
    public function messages(){ return $this->hasMany(CallMessage::class); }

}
