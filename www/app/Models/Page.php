<?php
// app/Models/Site.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Page extends Model {
    protected $fillable=['site_id','title','path','sort'];
    public function blocks(){ return $this->hasMany(Block::class)->orderBy('sort'); }
}
