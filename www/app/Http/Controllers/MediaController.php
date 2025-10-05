<?php
// app/Http/Controllers/MediaController.php
namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

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
        try {
            $r->validate([
                'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,svg,pdf,mp4|max:20480',
            ]);

            $f = $r->file('file');
            Log::info(
                'upload start',
                [
                    'size'=>$f->getSize(),
                    'memory'=>ini_get('memory_limit')
                ]);// ← null になってないか要点検
            $bytes = base64_encode(file_get_contents($f->getRealPath()));

            $m = Media::create([
                'disk'          => 'db_b64',
                'original_name' => $f->getClientOriginalName(),
                'mime'          => $f->getClientMimeType(),
                'size'          => $f->getSize(),
                'site_id'       => $r->input('site_id'),
                'bytes'         => $bytes,
            ]);

            return response()->json([
                'id'        => $m->id,
                'site_id'   => $m->site_id,
                'name'      => $m->original_name,
                'mime'      => $m->mime,
                'size'      => $m->size,
                'url' => url("/api/media/{$m->id}"),
            ]);
        } catch (\Throwable $e) {
            Log::error('media upload failed', ['e'=>$e]);
            // 一時的に詳細返す（直ったら消す）
            return response()->json([
                'message' => $e->getMessage(),
                'type'    => get_class($e),
            ], 500);
        }
    }

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
        $bin = base64_decode($m->bytes);
        return \Illuminate\Support\Facades\Response::make($bin, 200, [
            'Content-Type'   => $m->mime ?: 'application/octet-stream',
            'Content-Length' => (string)($m->size ?? strlen($bin)),
            'Cache-Control'  => 'public, max-age=31536000, immutable',
        ]);
    }

}
