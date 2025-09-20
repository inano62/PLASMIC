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
    public function page(Request $r, $slug)
    {

        $path = $r->query('path', '/');

        // 1) サイトは必須（無ければ404）
        $site = Site::where('slug', $slug)->firstOrFail();

        // 2) ページは任意（無ければフォールバックJSONを返す）
        $page = Page::where('site_id', $site->id)->where('path', $path)->first();

        if (!$page) {
            // ダミー情報を返す
            return response()->json([
                'site' => $site->only('id','title','slug','meta'),
                'page' => [
                    'id'    => 0,
                    'title' => $site->title,
                    'path'  => $path,
                    'sort'  => 0,
                    'blocks'=> [
                        [
                            'id'   => 1,
                            'type' => 'hero',
                            'sort' => 1,
                            'data' => [
                                'title'    => $site->title,
                                'subtitle' => '所在地: 未登録 / 電話: 未登録',
                                'actions'  => [
                                    ['label'=>'予約する','href'=>"/book?site={$site->id}"],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        }
        // ← ここからは従来どおり（Page が無ければフォールバックでもOK）
        $page = Page::where('site_id', $site->id)->where('path', $path)->first();
//        if (!$page) {
//            $tenant = $tenant ?: Tenant::find($site->tenant_id);
//            return response()->json([
//                'site' => $site->only('id','title','slug','meta'),
//                'page' => [
//                    'id'=>0,'title'=>$site->title ?? ($tenant->display_name ?? '事務所ページ'),'path'=>$path,'sort'=>0,
//                    'blocks'=>[
//                        ['id'=>0,'type'=>'hero','sort'=>1,'data'=>[
//                            'title'=>$site->title ?? ($tenant->display_name ?? '事務所ページ'),
//                            'subtitle'=>trim(($tenant->region ?? '').'・'.($tenant->type ?? '')),
//                            'actions'=>[['label'=>'予約する','href'=>"/book?tenant={$tenant->id}"]],
//                        ]],
//                    ],
//                ],
//            ]);
//        }

        $page->load(['blocks' => fn($q) => $q->orderBy('sort')]);

        return response()->json([
            'site' => $site->only('id','title','slug','meta'),
            'page' => [
                'id'=>$page->id,'title'=>$page->title,'path'=>$page->path,'sort'=>$page->sort,
                'blocks'=>$page->blocks->map(function($b){
                    $data = is_array($b->data) ? $b->data : (json_decode($b->data,true) ?? []);
                    if (!empty($data['imgId']) && empty($data['imgUrl'])) {
                        if ($m = Media::find($data['imgId'])) $data['imgUrl'] = Storage::disk('public')->url($m->path);
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
