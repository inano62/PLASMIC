// frontend/src/pages/admin/site/AdminSiteBuilder.tsx
import { useEffect, useState } from "react";
import { Button } from "react-bootstrap";
import { api } from "../../lib/api";

type Site  = { id:number; title:string; slug:string; meta?:Record<string, unknown> };
type BlockData = {
    kicker?: string;
    title?: string;
    subtitle?: string;
    btnText?: string;
    btnHref?: string;
    imgId?: number | null;
    imgUrl?: string;
    items?: { title?: string; text?: string }[];
    text?: string;
    [key: string]: string | number | { title?: string; text?: string }[] | number | null | undefined;
};

type Block = { id:number; type:string; sort:number; data: BlockData };
type Page  = { id:number; title:string; path:string; sort:number; blocks: Block[] };

const SITE_ID = 1;

/* ===== ブロック別ミニエディタ ===== */
function HeroEditor({data, onSave}:{data: BlockData; onSave:(diff: BlockData)=>void}) {
    const [v,setV] = useState<BlockData>({
        kicker:   data?.kicker ?? "",
        title:    data?.title ?? "",
        subtitle: data?.subtitle ?? "",
        btnText:  data?.btnText ?? "",
        btnHref:  data?.btnHref ?? "",
        imgId:    data?.imgId ?? null,
        imgUrl:   data?.imgUrl ?? "",
    });

    async function onPick(e: React.ChangeEvent<HTMLInputElement>) {
        const f = e.target.files?.[0];
        if (!f) return;
        const fd = new FormData();
        fd.append("file", f);
        // Use api.post for file upload, assuming it returns {id, url}
        const response = await api.post("/media", fd, {
            headers: { "Content-Type": "multipart/form-data" }
        });
        const uploaded = response.data as { id: number; url: string };
        setV({ ...v, imgId: uploaded.id, imgUrl: uploaded.url });
    }

    return (
        <div className="row g-3">
            {["kicker","title","subtitle","btnText","btnHref"].map(k=>(
                <div className={k==="subtitle" ? "col-12" : "col-md-6"} key={k}>
                    <label className="form-label">{k}</label>
                    <input className="form-control" value={typeof v[k] === "string" || typeof v[k] === "number" ? v[k] : ""}
                           onChange={e=>setV({...v, [k]: e.target.value})}/>
                </div>
            ))}
            <div className="col-md-6">
                <label className="form-label">ヒーロー画像</label>
                <input type="file" className="form-control" accept="image/*" onChange={onPick}/>
                {v.imgUrl && <img src={v.imgUrl} className="img-fluid mt-2 rounded-3" />}
            </div>
            <div className="col-12">
                <Button onClick={()=>onSave(v)}>このブロックを保存</Button>
            </div>
        </div>
    );
}

function FeaturesEditor({data, onSave}:{data: BlockData; onSave:(diff: BlockData)=>void}) {
    const [items,setItems] = useState<{ title?: string; text?: string }[]>(data?.items || []);
    return (
        <div>
            {items.map((it, idx)=>(
                <div className="row g-2 align-items-end mb-2" key={idx}>
                    <div className="col-md-4">
                        <label className="form-label">title</label>
                        <input className="form-control" value={it.title||""}
                               onChange={e=>{ const a=[...items]; a[idx]={...a[idx], title:e.target.value}; setItems(a); }}/>
                    </div>
                    <div className="col-md-6">
                        <label className="form-label">text</label>
                        <input className="form-control" value={it.text||""}
                               onChange={e=>{ const a=[...items]; a[idx]={...a[idx], text:e.target.value}; setItems(a); }}/>
                    </div>
                    <div className="col-md-2">
                        <Button size="sm" variant="outline-danger"
                                onClick={()=>{ const a=[...items]; a.splice(idx,1); setItems(a); }}>削除</Button>
                    </div>
                </div>
            ))}
            <Button size="sm" className="me-2" onClick={()=>setItems([...items, {title:"",text:""}])}>+ 追加</Button>
            <Button onClick={()=>onSave({items})}>このブロックを保存</Button>
        </div>
    );
}

function CtaEditor({data, onSave}:{data: BlockData; onSave:(diff: BlockData)=>void}) {
    const [v,setV]=useState<BlockData>({ text:data?.text||"", btnText:data?.btnText||"", btnHref:data?.btnHref||"" });
    return (
        <div className="row g-3">
            {["text","btnText","btnHref"].map(k=>(
                <div className="col-md-6" key={k}>
                    <label className="form-label">{k}</label>
                    <input className="form-control" value={typeof v[k] === "string" || typeof v[k] === "number" ? v[k] : ""} 
                           onChange={e=>setV({...v, [k]:e.target.value})}/>
                </div>
            ))}
            <div className="col-12"><Button onClick={()=>onSave(v)}>このブロックを保存</Button></div>
        </div>
    );
}

/* ===== メイン ===== */
export default function AdminSiteBuilder() {
    const [site, setSite]     = useState<Site|null>(null);
    const [pages, setPages]   = useState<Page[]>([]);
    const [loading, setLoading] = useState(true);

    async function load() {
        setLoading(true);
        const res = await api.get<{site: Site; pages: Page[]}>(`/admin/sites/${SITE_ID}`);
        setSite(res.data.site);
        setPages(res.data.pages);
        setLoading(false);
    }
    useEffect(()=>{ load(); }, []);

    async function saveSite() {
        if (!site) return;
        await api.put(`/admin/sites/${SITE_ID}`, { title: site.title, slug: site.slug, meta: site.meta });
        alert("保存しました");
    }

    async function publishAndOpen() {
        const res = await api.post<{slug?:string}>(`/admin/sites/${SITE_ID}/publish`, {});
        const slug = res.data.slug || site?.slug || "";
        window.open(`/s/${slug}/`, "_blank");
    }

    async function addPage(path="/", title="Home") {
        await api.post(`/admin/sites/${SITE_ID}/pages`, { path, title });
        await load();
    }

    async function addBlock(pageId:number, type:string) {
        await api.post(`/admin/pages/${pageId}/blocks`, { type, data:{} });
        await load();
    }

    async function updateBlock(b: Block, diff: BlockData) {
        const next = { ...b, data: { ...b.data, ...diff } };
        await api.put(`/admin/blocks/${b.id}`, { data: next.data, sort: next.sort });
        await load();
    }

    async function reorder(page: Page, upId:number, dir:-1|1) {
        const idx = page.blocks.findIndex(x=>x.id===upId);
        const arr = [...page.blocks];
        const j = idx + dir;
        if (j < 0 || j >= arr.length) return;
        [arr[idx], arr[j]] = [arr[j], arr[idx]];
        await api.post(`/admin/pages/${page.id}/reorder`, { ids: arr.map(x=>x.id) });
        await load();
    }

    async function removeBlock(id:number) {
        if (!confirm("削除しますか？")) return;
        await api.delete(`/admin/blocks/${id}`);
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
                        <div key={b.id} className="border rounded-4 p-3 mb-3">
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
