<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallMessage extends Model {
    public $timestamps = false;
    protected $fillable = ['call_log_id','sender_user_id','content','sent_at'];
    protected $casts = ['sent_at'=>'datetime'];

}
