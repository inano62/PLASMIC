<?php
//app/Http/Controllers/PublicSiteApiController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Site, Page};

class PublicSiteApiController extends Controller
{
    // /api/public/sites/{slug}
    public function site($slug)
    {
        $site  = Site::where('slug', $slug)->firstOrFail();
        $pages = $site->pages()->orderBy('sort')->get(['id','title','path','sort']);

        return response()->json([
            'site'  => ['title' => $site->title, 'slug' => $site->slug],
            'pages' => $pages,
        ]);
    }

    // /api/public/sites/{slug}/page?path=/about  （path 未指定なら "/"）
    public function page(Request $r, $slug)
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        $path = '/'.ltrim($r->query('path','/'), '/');

        $page = $site->pages()->where('path', $path)->first()
            ?? $site->pages()->where('path', '/')->firstOrFail();

        // published_json があれば優先、無ければ live データで代替
        $payload = $page->published_json
            ? json_decode($page->published_json, true)
            : [
                'title'  => $page->title,
                'path'   => $page->path,
                'blocks' => $page->blocks()->orderBy('sort')->get(['type','data','sort'])->toArray(),
            ];

        return response()->json([
            'site' => ['title' => $site->title, 'slug' => $site->slug],
            'page' => $payload,
            'nav'  => $site->pages()->orderBy('sort')->get(['title','path']),
        ]);
    }
}
