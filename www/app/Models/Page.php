<?php
// app/Models/Page.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Page extends Model {
    protected $fillable = ['site_id','title','path','sort','published_html','published_at'];
    protected $casts = [];
    public function site(){ return $this->belongsTo(Site::class); }
    public function blocks(){ return $this->hasMany(Block::class)->orderBy('sort'); }
}
