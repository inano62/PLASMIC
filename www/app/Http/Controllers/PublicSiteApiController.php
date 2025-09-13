<?php
// app/Http/Controllers/PublicSiteApiController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;   // ★ これが必要
use App\Models\{Media, Site, Page};

class PublicSiteApiController extends Controller
{
    // GET /api/public/sites/{slug}
    public function site($slug)
    {
        // まず publish 済み JSON があればそれを返す
        $published = "published/{$slug}.json";
        if (Storage::disk('public')->exists($published)) {
            return response()->json(
                json_decode(Storage::disk('public')->get($published), true)
            );
        }

        // フォールバック：DBから同じ形に組み立てる（pages と blocks を含む）
        $site = Site::where('slug', $slug)
            ->with(['pages.blocks' => fn($q) => $q->orderBy('sort')])
            ->firstOrFail();

        $payload = [
            'site' => [
                'id'    => $site->id,
                'title' => $site->title,
                'slug'  => $site->slug,
                'meta'  => $site->meta,
            ],
            'pages' => $site->pages->sortBy('sort')->map(function ($p) {
                return [
                    'id'     => $p->id,
                    'title'  => $p->title,
                    'path'   => $p->path,
                    'sort'   => $p->sort,
                    'blocks' => $p->blocks->sortBy('sort')->map(function ($b) {
                        // 常に配列にしておく（casts が無い環境でも安全）
                        $data = is_array($b->data) ? $b->data : (json_decode($b->data, true) ?? []);
                        // 旧データ互換: imgId があれば imgUrl を補完
                        if (!empty($data['imgId']) && empty($data['imgUrl'])) {
                            if ($m = Media::find($data['imgId'])) {
                                $data['imgUrl'] = Storage::disk('public')->url($m->path);
                            }
                        }
                        // bgUrl / avatarUrl は URL をそのまま通す（ここで触らない）
                        return [
                            'id'   => $b->id,
                            'type' => $b->type,
                            'sort' => $b->sort,
                            'data' => $data,
                        ];
                    })->values(),
                ];
            })->values(),
        ];

        return response()->json($payload);
    }

    // GET /api/public/sites/{slug}/page?path=/about
    public function page(Request $r, $slug)
    {
        $path = $r->query('path', '/');

        $site = Site::where('slug', $slug)->firstOrFail();
        $page = Page::where('site_id', $site->id)
            ->where('path', $path)
            ->firstOrFail();

        $page->load(['blocks' => fn($q) => $q->orderBy('sort')]);

        return response()->json([
            'site' => $site->only('id', 'title', 'slug', 'meta'),
            'page' => [
                'id'     => $page->id,
                'title'  => $page->title,
                'path'   => $page->path,
                'sort'   => $page->sort,
                'blocks' => $page->blocks->map(function ($b) {
                    $data = is_array($b->data) ? $b->data : (json_decode($b->data, true) ?? []);
                    // 旧データ互換: imgId があれば imgUrl を補完
                    if (!empty($data['imgId']) && empty($data['imgUrl'])) {
                        if ($m = Media::find($data['imgId'])) {
                            $data['imgUrl'] = Storage::disk('public')->url($m->path);
                        }
                    }
                    // bgUrl / avatarUrl は URL のままでOK
                    return [
                        'id'   => $b->id,
                        'type' => $b->type,
                        'sort' => $b->sort,
                        'data' => $data,
                    ];
                })->values(),
            ],
        ]);
    }
}
