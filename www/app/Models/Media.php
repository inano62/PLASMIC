<?php
// app/Models/Site.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Storage;

// app/Models/Media.php
class Media extends Model {
    protected $fillable=['disk','path','mime','size','original_name'];
    protected $appends=['url'];
//    public function getUrlAttribute(){ return Storage::disk($this->disk)->url($this->path); }
    public function getUrlAttribute():string
    {
        return Storage::disk($this->disk ?? 'public')->url($this->path);
    }
}
