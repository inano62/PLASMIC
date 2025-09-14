<?php

// app/Http/Controllers/PublicSiteController.php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Site,Page};

class PublicSiteController extends Controller
{
    public function showBySlug(string $slug)
    {
        $site = Site::with('office')->where('slug', $slug)->firstOrFail();
        return response()->json($site);
    }
    public function index(Request $r) {
        $q = Inquiry::query();
        if ($r->filled('status')) $q->where('status',$r->string('status'));
        return $q->orderByDesc('id')->limit(50)->get();
    }

    public function show($id) {
        $inq = Inquiry::findOrFail($id);
        return $inq;
    }

    public function update(Request $r, $id) {
        $inq = Inquiry::findOrFail($id);
        $inq->fill($r->only(['status','preferred_at']))->save();
        return $inq;
    }
    public function storeInquiry(Request $req) {
        $data = $req->validate([
            'site_slug'    => ['nullable','string','max:100'],
            'name'         => ['nullable','string','max:255'],
            'email'        => ['required','email','max:255'],
            'phone'        => ['nullable','string','max:50'],
            'address'      => ['nullable','string','max:255'],
            'topic'        => ['nullable','string','max:255'],
            'message'      => ['required','string'],
            'preferred_at' => ['nullable','date'],
        ]);
        $inq = Inquiry::create($data);

        // 管理者通知（MailHog へ）
        try {
            Mail::raw(
                "新しいお問い合わせ\n".
                "サイト: {$inq->site_slug}\n".
                "名前: {$inq->name}\n".
                "メール: {$inq->email}\n".
                "電話: {$inq->phone}\n".
                "住所: {$inq->address}\n".
                "件名: {$inq->topic}\n".
                "希望日時: {$inq->preferred_at}\n\n".
                "{$inq->message}\n",
                fn($m)=>$m->to('owner@example.test')->subject('[問い合わせ] '.$inq->topic)
            );
        } catch (\Throwable $e) { \Log::warning('inquiry mail failed: '.$e->getMessage()); }

        // 自動返信（任意）
        try {
            Mail::raw(
                "お問い合わせありがとうございます。担当者より折り返します。\n\n受付内容:\n{$inq->message}",
                fn($m)=>$m->to($inq->email)->subject('【自動返信】お問い合わせを受け付けました')
            );
        } catch (\Throwable $e) {}

        return response()->json(['id'=>$inq->id], 201);
    }
//    public function show(Request $r, $slug, $any=null){
//        $site = Site::where('slug',$slug)->firstOrFail();
//        $path = '/'.ltrim($any ?? '/', '/');       // 例: "", "about" → "/about"
//        $page = $site->pages()->where('path',$path)->first()
//            ?? $site->pages()->where('path','/')->firstOrFail();
//        if ($page->published_html) {
//            // そのまま配信（ヘッダー等はlayout内に含める）
//            return response($page->published_html);
//        }
//        // 未公開でも見せたいときは再構築するなら以下
//        return view('public.layout', ['site'=>$site, 'page'=>$page]);
//    }
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
