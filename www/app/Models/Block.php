<?php
// app/Models/Block.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Block extends Model {
    protected $fillable = ['page_id','type','data','sort'];
    protected $casts = ['data'=>'array'];
    public function page(){ return $this->belongsTo(Page::class); }
}
