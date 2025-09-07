<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantUser
{
    protected $table = 'tenant_users';         // テーブル名
    protected $fillable = ['tenant_id','user_id','role'];
    public $timestamps = true;
}
