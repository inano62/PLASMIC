// frontend/src/pages/admin/site/AdminSiteBuilder.tsx
import { useEffect, useState } from "react";
import { Button } from "react-bootstrap";
import API from "@/lib/api";

import HeroEditor     from "@/components/site-builder/HeroEditor";
import FeaturesEditor from "@/components/site-builder/FeaturesEditor";
import CtaEditor      from "@/components/site-builder/CtaEditor";


type Site  = { id:number; title:string; slug:string; meta?:any };
type Block = { id:number; type:string; sort:number; data:any };
type Page  = { id:number; title:string; path:string; sort:number; blocks: Block[] };

const SITE_ID = 1;


/* ===== メイン ===== */
export default function Builder() {
    const [site, setSite]     = useState<Site|null>(null);
    const [pages, setPages]   = useState<Page[]>([]);
    const [loading, setLoading] = useState(true);

    async function load() {
        setLoading(true);
        const s = await API.getJson<{site: Site; pages: Page[]}>(`/admin/sites/${SITE_ID}`);
        setSite(s.site);
        setPages(s.pages);
        setLoading(false);
    }
    useEffect(()=>{ load(); }, []);

    async function saveSite() {
        if (!site) return;
        await API.jput(`/admin/sites/${SITE_ID}`, { title: site.title, slug: site.slug, meta: site.meta });
        alert("保存しました");
    }

    async function publishAndOpen() {
        const res = await API.postJson<{slug?:string}>(`/admin/sites/${SITE_ID}/publish`, {});
        const slug = res.slug || site?.slug || "";
        window.open(`/s/${slug}/`, "_blank");
    }

    async function addPage(path="/", title="Home") {
        await API.postJson(`/admin/sites/${SITE_ID}/pages`, { path, title });
        await load();
    }

    async function addBlock(pageId:number, type:string) {
        await API.postJson(`/admin/pages/${pageId}/blocks`, { type, data:{} });
        await load();
    }

    async function updateBlock(b: Block, diff:any) {
        const next = { ...b, data: { ...b.data, ...diff } };
        await API.jput(`/admin/blocks/${b.id}`, { data: next.data, sort: next.sort });
        await load();
    }

    async function reorder(page: Page, upId:number, dir:-1|1) {
        const idx = page.blocks.findIndex(x=>x.id===upId);
        const arr = [...page.blocks];
        const j = idx + dir;
        if (j < 0 || j >= arr.length) return;
        [arr[idx], arr[j]] = [arr[j], arr[idx]];
        await API.postJson(`/admin/pages/${page.id}/reorder`, { ids: arr.map(x=>x.id) });
        await load();
    }

    async function removeBlock(id:number) {
        if (!confirm("削除しますか？")) return;
        await API.jdel(`/admin/blocks/${id}`);
        await load();
    }

    if (loading) return <div className="p-4">読み込み中…</div>;
    if (!site)    return <div className="p-4">サイトが見つかりません</div>;

    return (
        <div className="container py-4">
            <div className="d-flex gap-3 align-items-end">
                <div className="flex-grow-1">
                    <div className="form-label">サイト名</div>
                    <input className="form-control" value={site.title}
                           onChange={e=>setSite({...site!, title:e.target.value})}/>
                </div>
                <div>
                    <div className="form-label">スラッグ</div>
                    <input className="form-control" value={site.slug}
                           onChange={e=>setSite({...site!, slug:e.target.value})}/>
                </div>
                <Button className="ms-auto" onClick={saveSite}>保存</Button>
                <Button variant="outline-primary" onClick={publishAndOpen}>公開ページで確認</Button>
            </div>

            {pages.map(p=>(
                <div key={p.id} className="mt-4">
                    <div className="d-flex align-items-center mb-2">
                        <h5 className="mb-0">{p.title} <small className="text-muted">{p.path}</small></h5>
                        <div className="ms-auto d-flex gap-2">
                            <Button size="sm" onClick={()=>addBlock(p.id,"hero")}>+ Hero</Button>
                            <Button size="sm" onClick={()=>addBlock(p.id,"features")}>+ Features</Button>
                            <Button size="sm" onClick={()=>addBlock(p.id,"cta")}>+ CTA</Button>
                            <Button size="sm" variant="outline-secondary" onClick={()=>addPage("/new","New Page")}>+ Page</Button>
                        </div>
                    </div>

                    {p.blocks.map(b=>(
                        <div key={b.id ?? `${b.type}-${b.sort ?? 0}`} className="border rounded-4 p-3 mb-3">
                            <div className="d-flex align-items-center mb-2">
                                <div className="badge text-bg-secondary me-2">{b.type}</div>
                                <div className="ms-auto d-flex gap-2">
                                    <Button size="sm" variant="light" onClick={()=>reorder(p,b.id,-1)}>↑</Button>
                                    <Button size="sm" variant="light" onClick={()=>reorder(p,b.id, 1)}>↓</Button>
                                    <Button size="sm" variant="outline-danger" onClick={()=>removeBlock(b.id)}>削除</Button>
                                </div>
                            </div>

                            {b.type==="hero"     && <HeroEditor     data={b.data} onSave={(d)=>updateBlock(b,d)} />}
                            {b.type==="features" && <FeaturesEditor data={b.data} onSave={(d)=>updateBlock(b,d)} />}
                            {b.type==="cta"      && <CtaEditor      data={b.data} onSave={(d)=>updateBlock(b,d)} />}
                        </div>
                    ))}
                </div>
            ))}
        </div>
    );
}
