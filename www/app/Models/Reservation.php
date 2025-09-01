<?php
// app/Models/Reservation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;


// app/Models/Reservation.php
class Reservation extends Model {
    protected $fillable = ['room_name','host_code','guest_code','status'];
    protected $appends  = ['host_url','guest_url'];

    public function getHostUrlAttribute(): string {
        return frontend_origin().'/join/host/'.$this->host_code;
    }
    public function getGuestUrlAttribute(): string {
        return frontend_origin().'/join/guest/'.$this->guest_code;
    }
}

//class Reservation extends Model
//{
//    protected $fillable = ['room_name','host_code','guest_code','status'];
//    protected $appends  = ['host_url','guest_url'];
//
//    public function getHostUrlAttribute(): string {
//        return frontend_origin().'/join/host/'.$this->host_code;
//    }
//    public function getGuestUrlAttribute(): string {
//        return frontend_origin().'/join/guest/'.$this->guest_code;
//    }
//}
