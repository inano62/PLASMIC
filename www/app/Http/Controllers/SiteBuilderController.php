<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Site;
use App\Models\Page;
use App\Models\Block;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Exception;

class SiteBuilderController extends Controller
{
    /* =========================
     *   ベース実装（旧API名）
     * ========================= */

    // GET /admin/sites/me/{id}
    public function show(Request $r)
    {
        $user = $r->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $tenantId = $user->id;

        $site = Site::firstOrCreate(
            ['tenant_id'=>$tenantId],
            ['title'=>'Demo Site','slug'=>"tenant-$tenantId",'meta'=>['theme'=>'default']]
        );

        Page::firstOrCreate(
            ['site_id'=>$site->id,'path'=>'/'],
            ['title'=>'Home','sort'=>1]
        );

        $pages = Page::where('site_id',$site->id)
            ->orderBy('sort')
            ->with(['blocks'=>fn($q)=>$q->orderBy('sort')])
            ->get();

        return response()->json(['site'=>$site,'pages'=>$pages]);
    }


    // PUT /admin/sites/{id}
    public function update(Request $r, $id)
    {
        $site = Site::findOrFail($id);
        $site->title = $r->input('title', $site->title);
        $site->slug = $r->input('slug', $site->slug);
        $site->meta = $r->input('meta', $site->meta);
        $site->save();
        return response()->json(['ok' => true]);
    }

    // POST /admin/sites/{id}/publish
    public function publish(int $id)
    {
        $site = Site::with(['pages.blocks' => fn($q) => $q->orderBy('sort')])->findOrFail($id);

        $payload = [
            'site' => [
                'id' => $site->id,
                'title' => $site->title,
                'slug' => $site->slug,
                'meta' => $site->meta,
            ],
            'pages' => $site->pages->sortBy('sort')->map(function ($p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'path' => $p->path,
                    'sort' => $p->sort,
                    'blocks' => $p->blocks->sortBy('sort')->map(function ($b) {
                        // ★ data をそのまま渡す（avatarUrl / bgUrl を落とさない）
                        $data = is_array($b->data) ? $b->data : (json_decode($b->data, true) ?? []);
                        return [
                            'id' => $b->id,
                            'type' => $b->type,
                            'sort' => $b->sort,
                            'data' => $data,
                        ];
                    })->values(),
                ];
            })->values(),
        ];

        // 公開用に JSON を書き出し（簡易実装）
        Storage::disk('public')->put("published/{$site->slug}.json",
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return response()->json(['ok' => true, 'slug' => $site->slug]);
    }

    // POST /admin/sites/{id}/pages  {path,title}
    public function addPage(Request $r, $id)
    {
        $max = Page::where('site_id', $id)->max('sort') ?? 0;

        $p = new Page();
        $p->site_id = $id;
        $p->title = $r->input('title', 'Page');
        $p->path = $r->input('path', '/');
        $p->sort = $max + 1;
        $p->save();

        return response()->json(['id' => $p->id]);
    }

    // POST /admin/pages/{pageId}/blocks  {type,data?}
    public function addBlock(Request $r, $pageId)
    {
        $max = Block::where('page_id', $pageId)->max('sort') ?? 0;

        $b = new Block();
        $b->page_id = $pageId;
        $b->type = $r->input('type', 'hero');
        $b->data = $r->input('data', []);
        $b->sort = $max + 1;
        $b->save();

        return response()->json(['id' => $b->id]);
    }

    // PUT /admin/blocks/{id}  {data,sort?}
    public function updateBlock(Request $r, $id)
    {
        $block = Block::findOrFail($id);
        $data = $r->input('data', []);
        if (!is_array($data)) $data = json_decode($data, true) ?? [];

        $block->data = $data;                          // 丸ごと置換
        $block->sort = $r->input('sort', $block->sort);
        $block->save();

        return response()->json($block);
    }

    // POST /admin/pages/{pageId}/reorder  {ids:[...]}
    public function reorder(Request $r, $pageId)
    {
        $ids = $r->input('ids', []);
        DB::transaction(function () use ($ids) {
            foreach ($ids as $i => $id) {
                Block::where('id', $id)->update(['sort' => $i + 1]);
            }
        });
        return response()->json(['ok' => true]);
    }

    // DELETE /admin/blocks/{id}
    public function deleteBlock($id)
    {
        $block = Block::findOrFail($id);
        $block->delete();
        return response()->json(['ok' => true]);
    }

    /* ======================================
     *   ラッパー（新API名 → 旧API名へ委譲）
     *   ※ルートモデル束縛でも数値でもOK
     * ====================================== */

    public function showSite($site)
    {
        $id = is_object($site) ? $site->id : $site;
        return $this->show($id);
    }

    public function updateSite(Request $r, $site)
    {
        $id = is_object($site) ? $site->id : $site;
        return $this->update($r, $id);
    }

    public function createPage(Request $r, $site)
    {
        $id = is_object($site) ? $site->id : $site;
        return $this->addPage($r, $id);
    }

    public function createBlock(Request $r, $page)
    {
        $pageId = is_object($page) ? $page->id : $page;
        return $this->addBlock($r, $pageId);
    }

    public function reorderBlocks(Request $r, $page)
    {
        $pageId = is_object($page) ? $page->id : $page;
        return $this->reorder($r, $pageId);
    }

    public function destroyBlock($block)
    {
        $id = is_object($block) ? $block->id : $block;
        return $this->deleteBlock($id);
    }
}
