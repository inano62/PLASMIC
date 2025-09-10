<?php
// app/Models/Site.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

// app/Models/Media.php
class Media extends Model {
    protected $fillable=['disk','path','mime','size'];
    protected $appends=['url'];
    public function getUrlAttribute(){ return Storage::disk($this->disk)->url($this->path); }
}
