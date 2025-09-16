<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    //
    protected $fillable = [
        'site_slug','name','email','phone','address','topic','message','preferred_at','status'
    ];
}
