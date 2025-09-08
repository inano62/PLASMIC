<?php

// app/Http/Controllers/PublicSiteController.php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Site,Page};

class PublicSiteController extends Controller
{
    public function show(Request $r, $slug, $any=null){
        $site = Site::where('slug',$slug)->firstOrFail();
        $path = '/'.ltrim($any ?? '/', '/');       // 例: "", "about" → "/about"
        $page = $site->pages()->where('path',$path)->first()
            ?? $site->pages()->where('path','/')->firstOrFail();
        if ($page->published_html) {
            // そのまま配信（ヘッダー等はlayout内に含める）
            return response($page->published_html);
        }
        // 未公開でも見せたいときは再構築するなら以下
        return view('public.layout', ['site'=>$site, 'page'=>$page]);
    }
    public function site($slug)
    {
        $site = Site::where('slug',$slug)->firstOrFail();
        $pages = $site->pages()->orderBy('sort')->get(['id','title','path']);
        return [ 'site'=>['title'=>$site->title,'slug'=>$site->slug], 'pages'=>$pages ];
    }


    public function page(Request $r, $slug)
    {
        $path = '/'.ltrim($r->query('path','/'), '/');
        $site = Site::where('slug',$slug)->firstOrFail();
        $page = $site->pages()->where('path',$path)->first()
            ?? $site->pages()->where('path','/')->firstOrFail();


// 公開スナップショットがあればそれを返す。なければ現在の生データで代用
        $payload = $page->published_json ? json_decode($page->published_json, true) : [
            'title'=>$page->title,
            'path'=>$page->path,
            'blocks'=>$page->blocks()->orderBy('sort')->get(['type','data','sort'])->toArray(),
        ];


        return [
            'site'=>['title'=>$site->title,'slug'=>$site->slug],
            'page'=>$payload,
            'nav'=>$site->pages()->orderBy('sort')->get(['title','path']),
        ];
    }
}
