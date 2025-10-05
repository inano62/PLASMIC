<?php
// app/Models/Site.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Storage;

// app/Models/Media.php
class Media extends Model {
    protected $fillable = ['disk','mime','size','original_name','bytes','site_id'];
    protected $appends=['url'];
        public function getUrlAttribute(): string
    {
        return url("/api/admin/media/{$this->id}");
    }



}
