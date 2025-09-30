<?php
// app/Http/Controllers/MediaController.php
namespace App\Http\Controllers;

use App\Models\Block;
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
            'path'=> $path,
            ]);
    }
    // 例
    public function update(Request $r, Block $block)
    {
        // 1) dataを配列に
        $data = $r->input('data', []);
        if (!is_array($data)) $data = json_decode($data, true) ?? [];

        // 2) 既存dataとマージ（フロントがdiffでも安全）
        $current = is_array($block->data) ? $block->data : (json_decode($block->data, true) ?? []);
        $merged  = array_replace_recursive($current, $data);

        $block->update([
            'data' => $merged,
            'sort' => $r->input('sort', $block->sort),
        ]);

        // 3) 未参照Mediaの削除（ここでなら新IDは既に参照済み）
        $usedIds = Block::query()
            ->pluck('data') // JSON列
            ->map(fn($d) => is_array($d) ? $d : json_decode($d, true))
            ->filter() // null除去
            ->flatMap(function($arr){
                // JSONから数値だけ取り出す（bgId, avatarId など）
                $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($arr));
                $ids = [];
                foreach ($it as $v) if (is_int($v)) $ids[] = $v;
                return $ids;
            })
            ->unique()
            ->values();

        Media::whereNotIn('id', $usedIds)->delete();

        return response()->json($block);
    }
    public function show($id) {
        $m = Media::findOrFail($id);

        return response()->file(storage_path("app/public/{$m->path}"));
    }

}
