<?php
// app/Models/Tenant.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


// app/Models/Reservation.php
class Tenant extends Model {
    protected $guarded = [];
    public function members() {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot('role')
            ->withTimestamps();
    }
    public function pros() {
        return $this->members()->whereIn('tenant_users.role', ['owner','pro']);
    }
    public function getRouteKeyName() { return 'slug'; }
    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot('role')
            ->withTimestamps();
    }
}
