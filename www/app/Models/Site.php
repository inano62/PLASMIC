<?php
// app/Models/Site.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Site extends Model {
    protected $fillable = ['title','slug','meta'];
    protected $casts = ['meta'=>'array'];
    public function pages(){ return $this->hasMany(Page::class)->orderBy('sort'); }
}
