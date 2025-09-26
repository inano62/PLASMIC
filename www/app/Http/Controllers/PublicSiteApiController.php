<?php
// app/Http/Controllers/PublicSiteApiController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;   // ★ これが必要
use App\Models\{Media, Site, Page,Tenant,User};

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
    public function page(Request $r, string $slug)
    {
        $path = $r->query('path', '/');

        // 1) Site or fallback-to-Tenant
        $site = Site::where('slug', $slug)->first();

        // Siteが無い場合は Tenant を見てダミーを返す（nullを触らない）
        if (!$site) {
            if ($t = Tenant::where('slug', $slug)->first()) {
                return response()->json([
                    'site' => [
                        'id'    => 0,
                        'title' => $t->display_name ?? $slug,
                        'slug'  => $t->slug,
                        'meta'  => [],
                    ],
                    'page' => [
                        'id'    => 0,
                        'title' => $t->display_name ?? $slug,
                        'path'  => $path,
                        'sort'  => 0,
                        'blocks'=> [[
                            'id'   => 1,
                            'type' => 'hero',
                            'sort' => 1,
                            'data' => [
                                'title'    => $t->display_name ?? $slug,
                                'subtitle' => '所在地: 未登録 / 電話: 未登録',
                                'actions'  => [['label'=>'予約する','href'=>"/book?tenant={$t->id}"]],
                            ],
                        ]],
                    ],
                ]);
            }

            // Tenant すら無いなら、slug だけで完全ダミー
            return response()->json([
                'site' => ['id'=>0, 'title'=>$slug, 'slug'=>$slug, 'meta'=>[]],
                'page' => [
                    'id'=>0,'title'=>$slug,'path'=>$path,'sort'=>0,
                    'blocks'=>[[
                        'id'=>1,'type'=>'hero','sort'=>1,
                        'data'=>[
                            'title'=>$slug,
                            'subtitle'=>'所在地: 未登録 / 電話: 未登録',
                            'actions'=>[['label'=>'予約する','href'=>"/book"]],
                        ],
                    ]],
                ],
            ]);
        }

        // 2) Page は任意。無ければダミーを返す（$site は確実に存在）
        $page = Page::where('site_id', $site->id)->where('path', $path)->first();

        if (!$page) {
            return response()->json([
                'site' => $site->only('id','title','slug','meta'),
                'page' => [
                    'id'=>0,'title'=>$site->title,'path'=>$path,'sort'=>0,
                    'blocks'=>[[
                        'id'=>1,'type'=>'hero','sort'=>1,
                        'data'=>[
                            'title'=>$site->title,
                            'subtitle'=>'所在地: 未登録 / 電話: 未登録',
                            'actions'=>[['label'=>'予約する','href'=>"/book?site={$site->id}"]],
                        ],
                    ]],
                ],
            ]);
        }

        // 3) 通常ルート
        $page->load(['blocks' => fn($q) => $q->orderBy('sort')]);

        return response()->json([
            'site' => $site->only('id','title','slug','meta'),
            'page' => [
                'id'   => $page->id,
                'title'=> $page->title,
                'path' => $page->path,
                'sort' => $page->sort,
                'blocks' => $page->blocks->map(function ($b) {
                    $data = is_array($b->data) ? $b->data : (json_decode($b->data, true) ?? []);
                    if (!empty($data['imgId']) && empty($data['imgUrl'])) {
                        if ($m = Media::find($data['imgId'])) {
                            $data['imgUrl'] = Storage::disk('public')->url($m->path);
                        }
                    }
                    return ['id'=>$b->id,'type'=>$b->type,'sort'=>$b->sort,'data'=>$data];
                })->values(),
            ],
        ]);
    }

    public function tenantsList(Request $r)
    {
        $q      = trim((string) $r->query('q', ''));
        $region = trim((string) $r->query('region', ''));
        $type   = trim((string) $r->query('type', ''));

        $query = Tenant::query()->orderByDesc('id');

        if ($q !== '')      $query->where('display_name', 'like', "%{$q}%");
        if ($region !== '') $query->where('region', $region);          // カラム名は実DBに合わせて
        if ($type !== '')   $query->where('business_type', $type);

        // ページサイズはUIに合わせて
        $items = $query->paginate(12);

        // UIが使いやすい形に整形（必要ならそのまま返してもOK）
        $items->getCollection()->transform(function ($t) {
            return [
                'id'     => $t->id,
                'name'   => $t->display_name,
                'region' => $t->region,
                'type'   => $t->type,
                'slug'   => $t->slug,                 // 公開ページ /s/{slug} へ誘導
                'title'  => $t->display_name,
                'home'   => $t->home_url,
            ];
        });

        return response()->json($items);
    }
}
