<?php
// app/Http/Controllers/MediaController.php
namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function store(Request $r) {
        $r->validate([
            'file'=> 'required|file|mimes:jpg,jpeg,png,gif,webp,svg,pdf,mp4|max:20480'
            ]);
        $path = $r->file('file')->store('uploads','public');
        return ['url'=>asset('storage/'.$path), 'path'=>$path];
    }
    public function upload(Request $r) {
        $r->validate(['file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,svg,pdf,mp4|max:20480',]);
        $f = $r->file('file');
        $path = $f->store('uploads', 'public'); // storage/app/public/uploads
        $m = Media::create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $f->getClientOriginalName(),
            'mime' => $f->getClientMimeType(),
            'size' => $f->getSize(),
        ]);
        return response()->json([
            'id'=>$m->id,
            'url' => Storage::disk('public')->url($path),
            ]);
    }
}
