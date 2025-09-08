<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function appointmentsAsClient(){ return $this->hasMany(Appointment::class, 'client_user_id'); }
    public function appointmentsAsLawyer(){ return $this->hasMany(Appointment::class, 'lawyer_user_id'); }

    // app/Models/User.php
    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot('role')
            ->withTimestamps();
    }
    public function isAdmin(): bool  { return $this->role === 'admin'; }
    public function isLawyer(): bool { return $this->role === 'lawyer'; }
    public function canBuildSite(): bool
    {
        return $this->role === 'admin'
            || ($this->role === 'lawyer' && $this->account_type === 'pro');
    }
    public function hasPro(): bool   { return $this->account_type === 'pro' || $this->isAdmin(); }
}
