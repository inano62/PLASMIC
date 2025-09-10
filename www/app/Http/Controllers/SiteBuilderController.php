<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Site;
use App\Models\Page;
use App\Models\Block;

class SiteBuilderController extends Controller
{
    /* =========================
     *   ベース実装（旧API名）
     * ========================= */

    // GET /admin/sites/{id}
    public function show($id)
    {
        $site  = Site::findOrFail($id);
        $pages = Page::where('site_id', $id)
            ->orderBy('sort')
            ->with(['blocks' => function($q){ $q->orderBy('sort'); }])
            ->get();

        return response()->json(['site' => $site, 'pages' => $pages]);
    }

    // PUT /admin/sites/{id}
    public function update(Request $r, $id)
    {
        $site = Site::findOrFail($id);
        $site->title = $r->input('title', $site->title);
        $site->slug  = $r->input('slug',  $site->slug);
        $site->meta  = $r->input('meta',  $site->meta);
        $site->save();
        return response()->json(['ok' => true]);
    }

    // POST /admin/sites/{id}/publish
    public function publish($id)
    {
        $site = Site::findOrFail($id);
        return response()->json(['slug' => $site->slug]);
    }

    // POST /admin/sites/{id}/pages  {path,title}
    public function addPage(Request $r, $id)
    {
        $max = Page::where('site_id', $id)->max('sort') ?? 0;

        $p = new Page();
        $p->site_id = $id;
        $p->title   = $r->input('title', 'Page');
        $p->path    = $r->input('path',  '/');
        $p->sort    = $max + 1;
        $p->save();

        return response()->json(['id' => $p->id]);
    }

    // POST /admin/pages/{pageId}/blocks  {type,data?}
    public function addBlock(Request $r, $pageId)
    {
        $max = Block::where('page_id', $pageId)->max('sort') ?? 0;

        $b = new Block();
        $b->page_id = $pageId;
        $b->type    = $r->input('type', 'hero');
        $b->data    = $r->input('data', []);
        $b->sort    = $max + 1;
        $b->save();

        return response()->json(['id' => $b->id]);
    }

    // PUT /admin/blocks/{id}  {data,sort?}
    public function updateBlock(Request $r, $id)
    {
        $b = Block::findOrFail($id);
        if ($r->has('data')) $b->data = $r->input('data');
        if ($r->has('sort')) $b->sort = (int)$r->input('sort');
        $b->save();
        return response()->json(['ok' => true]);
    }

    // POST /admin/pages/{pageId}/reorder  {ids:[...]}
    public function reorder(Request $r, $pageId)
    {
        $ids = $r->input('ids', []);
        DB::transaction(function() use ($ids) {
            foreach ($ids as $i => $id) {
                Block::where('id', $id)->update(['sort' => $i + 1]);
            }
        });
        return response()->json(['ok' => true]);
    }

    // DELETE /admin/blocks/{id}
    public function deleteBlock($id)
    {
        Block::where('id', $id)->delete();
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
