<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timeslot extends Model
{
    use HasFactory;

    protected $table = 'timeslots';

    protected $fillable = [
        'tenant_id', 'start_at', 'end_at', 'status', 'appointment_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    // リレーション（任意）
    public function tenant(){ return $this->belongsTo(Tenant::class); }
    public function appointment(){ return $this->belongsTo(Appointment::class); }

    // 便利スコープ
    public function scopeOfTenant($q, int $tenantId){ return $q->where('tenant_id', $tenantId); }
    public function scopeOpen($q){ return $q->where('status', 'open')->whereNull('appointment_id'); }
    public function scopeBetween($q, $from, $to){ return $q->whereBetween('start_at', [$from, $to]); }
}
