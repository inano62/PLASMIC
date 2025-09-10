<?php
// app/Models/Site.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

// app/Models/Block.php
class Block extends Model {
    protected $fillable=['page_id','type','sort','data'];
    protected $casts=['data'=>'array'];
}
