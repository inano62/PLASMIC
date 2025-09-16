<?php
// app/Models/Setting.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model {
    protected $table = 'settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key','value'];
    protected $casts = ['value' => 'array']; // JSON扱い
}
