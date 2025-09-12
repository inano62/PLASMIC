<?php
//app/Http/Controllers/PublicSiteApiController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Media, Site, Page};

class PublicSiteApiController extends Controller
{
    // /api/public/sites/{slug}
    public function site($slug)
    {
        $site = Site::where('slug', $slug)->firstOrFail();

        $pages = Page::where('site_id', $site->id)
            ->orderBy('sort')
            ->get(['id','title','path','sort']);

        return response()->json([
            'site'  => $site->only(['id','title','slug','meta']),
            'pages' => $pages,
        ]);
    }

    // /api/public/sites/{slug}/page?path=/about  （path 未指定なら "/"）
    public function page(Request $r, $slug)
    {
        $path = $r->query('path', '/');

        $site = Site::where('slug', $slug)->firstOrFail();

        $page = Page::where('site_id', $site->id)
            ->where('path', $path)
            ->firstOrFail();

        $page->load(['blocks' => function ($q) {
            $q->orderBy('sort');
        }]);

        return response()->json([
            'site' => $site->only('id','title','slug','meta'),
            'page' => [
                'id'    => $page->id,
                'title' => $page->title,
                'path'  => $page->path,
                'sort'  => $page->sort,
                'blocks'=> $page->blocks->map(function ($b) {
                    $data = $b->data ?? [];

                    // --- ここで imgId → imgUrl を補完 ---
                    if (isset($data['imgId']) && empty($data['imgUrl'])) {
                        if ($m = Media::find($data['imgId'])) {
                            // Media モデルの accessor で url を取得
                            if ($m =\App\Models\Media::find($data['imgId'])) {
                                $data['imgUrl'] = url($m->path);
                            }
                        }
                    }

                    return [
                        'id'   => $b->id,
                        'type' => $b->type,
                        'sort' => $b->sort,
                        'data' => $data,
                    ];
                }),
            ],
        ]);
    }
}
