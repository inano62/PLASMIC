<?php
// app/Models/Appointment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


// app/Models/Reservation.php
class Appointment extends Model {
    protected $fillable = [
        'tenant_id',
        'lawyer_user_id',
        'client_user_id',
        'client_name',
        'client_email',
        'client_phone',
        'starts_at',
        'ends_at',
        'status',
        'price_jpy',
        'room_name',
        'visitor_id',
        'purpose_title',
        'purpose_detail',
    ];
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];
    public function reservation(){ return $this->hasOne(Reservation::class); }
    public function client(){ return $this->belongsTo(User::class, 'client_user_id'); }
    public function lawyer(){ return $this->belongsTo(User::class, 'lawyer_user_id'); }
}
