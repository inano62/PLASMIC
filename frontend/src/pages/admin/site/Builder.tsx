// pages/admin/site/Builder.tsx
import { useEffect, useMemo, useState } from "react";
import { saveSite, loadSite } from "../../../lib/site/storage";
import type { SiteData, Block } from "../../../lib/site/ types.ts";
import {Button} from "react-bootstrap"; // or your button
import API from "@/lib/api";
const DEFAULT_SLUG = "judist-sakai";
type Site = { id:number; title:string; slug:string; meta?:any };
type Page = { id:number; title:string; path:string; sort:number; blocks: Block[] };
type Block = { id:number; type:string; sort:number; data:any };
const SITE_ID = 1;
export default function AdminSiteBuilder() {
    const [slug, setSlug] = useState(DEFAULT_SLUG);
    const [site, setSite] = useState<Site|null>(null);
    const [pages, setPages] = useState<Page[]>([]);
    const [loading, setLoading] = useState(true);

    async function load(){
        setLoading(true);
        const s = await API.get(`/admin/sites/${SITE_ID}`); // ← {site, pages: [{..., blocks: [...] }]}
        setSite(s.site);
        setPages(s.pages);
        setLoading(false);
    }
    useEffect(()=>{ load(); }, []);

    async function saveSite(){
        await API.put(`/admin/sites/${SITE_ID}`, { title: site!.title, slug: site!.slug, meta: site!.meta });
        alert("保存しました");
    }
    async function publishAndOpen(){
        const res = await API.post(`/admin/sites/${SITE_ID}/publish`, {});
        const slug = res.slug || site!.slug;
        window.open(`/s/${slug}/`, "_blank");
    }

    async function addPage(path="/", title="Home"){
        const p = await API.post(`/admin/sites/${SITE_ID}/pages`, { path, title });
        await load();
    }

    async function addBlock(pageId:number, type:string){
        await API.post(`/admin/pages/${pageId}/blocks`, { type });
        await load();
    }

    async function updateBlock(b: Block, diff:any){
        const next = {...b, data: {...b.data, ...diff}};
        await API.put(`/admin/blocks/${b.id}`, { data: next.data, sort: next.sort });
        // 楽するなら即リロード
        await load();
    }

    async function reorder(page: Page, upId:number, dir: -1|1){
        const idx = page.blocks.findIndex(x=>x.id===upId);
        const arr = [...page.blocks];
        const j = idx + dir;
        if (j<0 || j>=arr.length) return;
        [arr[idx], arr[j]] = [arr[j], arr[idx]];
        // サーバへ id 並びを送る
        await API.post(`/admin/pages/${page.id}/reorder`, { ids: arr.map(x=>x.id) });
        await load();
    }

    async function removeBlock(id:number){
        if(!confirm("削除しますか？")) return;
        await API.delete(`/admin/blocks/${id}`);
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
                            <Button size="sm" onClick={()=>addBlock(p.id,'hero')}>+ Hero</Button>
                            <Button size="sm" onClick={()=>addBlock(p.id,'features')}>+ Features</Button>
                            <Button size="sm" onClick={()=>addBlock(p.id,'cta')}>+ CTA</Button>
                        </div>
                    </div>

                    {p.blocks.map((b, i)=>(
                        <div key={b.id} className="border rounded-4 p-3 mb-3">
                            <div className="d-flex align-items-center mb-2">
                                <div className="badge text-bg-secondary me-2">{b.type}</div>
                                <div className="ms-auto d-flex gap-2">
                                    <Button size="sm" variant="light" onClick={()=>reorder(p, b.id, -1)}>↑</Button>
                                    <Button size="sm" variant="light" onClick={()=>reorder(p, b.id,  1)}>↓</Button>
                                    <Button size="sm" variant="outline-danger" onClick={()=>removeBlock(b.id)}>削除</Button>
                                </div>
                            </div>

                            {b.type==='hero' && (
                                <HeroEditor data={b.data} onSave={(diff)=>updateBlock(b,diff)}/>
                            )}
                            {b.type==='features' && (
                                <FeaturesEditor data={b.data} onSave={(diff)=>updateBlock(b,diff)}/>
                            )}
                            {b.type==='cta' && (
                                <CtaEditor data={b.data} onSave={(diff)=>updateBlock(b,diff)}/>
                            )}
                        </div>
                    ))}
                </div>
            ))}
        </div>
    );
}

// ── ブロック別エディタ（最小）
function HeroEditor({data, onSave}:{data:any; onSave:(diff:any)=>void}) {
    const [v,setV]=useState({...data});
    return (
        <div className="row g-3">
            {['kicker','title','subtitle','btnText','btnHref'].map(k=>(
                <div className={k==='subtitle'?'col-12':'col-md-6'} key={k}>
                    <label className="form-label">{k}</label>
                    <input className="form-control" value={v[k]||''} onChange={e=>setV({...v,[k]:e.target.value})}/>
                </div>
            ))}
            <div className="col-12"><Button onClick={()=>onSave(v)}>このブロックを保存</Button></div>
        </div>
    );
}
function FeaturesEditor({data, onSave}:{data:any; onSave:(diff:any)=>void}){
    const [items,setItems]=useState<any[]>(data?.items||[]);
    return (
        <div>
            {items.map((it,idx)=>(
                <div className="row g-2 align-items-end mb-2" key={idx}>
                    <div className="col-md-4">
                        <label className="form-label">title</label>
                        <input className="form-control" value={it.title||''} onChange={e=>{
                            const a=[...items]; a[idx]={...a[idx], title:e.target.value}; setItems(a);
                        }}/>
                    </div>
                    <div className="col-md-6">
                        <label className="form-label">text</label>
                        <input className="form-control" value={it.text||''} onChange={e=>{
                            const a=[...items]; a[idx]={...a[idx], text:e.target.value}; setItems(a);
                        }}/>
                    </div>
                    <div className="col-md-2">
                        <Button variant="outline-danger" onClick={()=>{
                            const a=[...items]; a.splice(idx,1); setItems(a);
                        }}>削除</Button>
                    </div>
                </div>
            ))}
            <Button size="sm" className="me-2" onClick={()=>setItems([...items,{title:'',text:''}])}>+ 追加</Button>
            <Button onClick={()=>onSave({items})}>このブロックを保存</Button>
        </div>
    );
}
function CtaEditor({data, onSave}:{data:any; onSave:(diff:any)=>void}){
    const [v,setV]=useState({...data});
    return (
        <div className="row g-3">
            {['text','btnText','btnHref'].map(k=>(
                <div className="col-md-6" key={k}>
                    <label className="form-label">{k}</label>
                    <input className="form-control" value={v[k]||''} onChange={e=>setV({...v,[k]:e.target.value})}/>
                </div>
            ))}
            <div className="col-12"><Button onClick={()=>onSave(v)}>このブロックを保存</Button></div>
        </div>
    );
}
