// frontend/src/pages/admin/site/AdminSiteBuilder.tsx
import {useCallback, useEffect, useState} from "react";
import { Button } from "react-bootstrap";
import HeroEditor     from "../../../components/site-builder/HeroEditor";
import FeaturesEditor from "../../../components/site-builder/FeaturesEditor";
import CtaEditor      from "../../../components/site-builder/CtaEditor";
import {useAuth} from "../../../contexts/auth.tsx";
import {useNavigate} from "react-router-dom";
import {api} from "../../../lib/api.ts";

type Site  = { id:number; title:string; slug:string; meta?:any };
type Block = { id:number; type:string; sort:number; data:any | null };
type Page  = { id:number; title:string; path:string; sort:number; blocks: Block[] };

const API = api;
export default function AdminSiteBuilder() {
    const [site, setSite] = useState<Site|null>(null);
    const [pages, setPages] = useState<Page[]>([]);
    const [err, setErr] = useState<string | null>(null);
    const nav = useNavigate();
    const { user, loading: authLoading } = useAuth();
    const [busy, setBusy] = useState(false);
    const SITE_ID = user?.id
    const [currentPage, setCurrentPage] = useState<Page|null>(null);

    const load = useCallback(async () => {
        setBusy(true);
        setErr(null);
        try {
            const { data: s } = await api.get('/admin/sites/me');
            setSite(s?.site ?? null);
            setPages(s?.pages ?? []);
            if (!currentPage && s?.pages?.length) setCurrentPage(s.pages[0]);
        } catch (e:any) {
            setErr(e?.response?.data?.message ?? e?.message ?? '読み込みに失敗しました');
        } finally {
            setBusy(false);
        }
    }, [currentPage]);

    useEffect(() => {
        if (authLoading) return;
        if (!user) { nav("/admin/login", { replace: true }); return; }
        load();
    }, [authLoading, user, load, nav]);
    // ---- 操作系 ----
    async function addPage(path = "/", title = "Home") {
        await API.post(`/admin/sites/${SITE_ID}/pages`, { path, title });
        await load();
    }

    async function addBlock(pageId: number, type: string) {
        await API.post(`/admin/pages/${pageId}/blocks`, { type, data: {} });
        await load();
    }

    async function saveSite() {
        if (!site) return;
        await API.put(`/admin/sites/${SITE_ID}`, { title: site.title, slug: site.slug, meta: site.meta });
        alert("保存しました");
    }


    async function updateBlock(b: Block, nextData:any) {
        await API.put(`/admin/blocks/${b.id}`, { data: nextData, sort: b.sort });
        // 楽観更新（必要なら）＋リロード
        setPages(old =>
            old.map(p => ({ ...p, blocks: p.blocks.map(x => x.id===b.id ? { ...x, data: nextData } : x) }))
        );
        await load();
    }

    async function reorder(page: Page, upId:number, dir:-1|1) {
        const idx = page.blocks.findIndex(x=>x.id===upId);
        const arr = [...page.blocks];
        const j = idx + dir;
        if (j < 0 || j >= arr.length) return;
        [arr[idx], arr[j]] = [arr[j], arr[idx]];
        await API.post(`/admin/pages/${page.id}/reorder`, { ids: arr.map(x=>x.id) });
        await load();
    }

    async function removeBlock(id:number) {
        if (!confirm("削除しますか？")) return;
        await API.delete(`/admin/blocks/${id}`);
        await load();
    }

    // 公開ページを開く
    function openPublic(slug: string, path: string) {
        const p = path.startsWith("/") ? path : `/${path}`;
        const url = `/s/${slug}${p.replace(/\/?$/, "/")}`;
        window.open(url, "_blank");
    }

    // 共通ボタンから追加する時は currentPage を使う
    const handleAdd = async (type: string) => {
        if (!currentPage) return alert("ページを選択してください");
        await addBlock(currentPage.id, type);
    };
    if (authLoading) return <div className="p-4">認証確認中…</div>;
    if (!user) return null;
    // if (authLoading || busy) return <div className="p-4">読み込み中…</div>;
    if (err) return <div className="p-4 text-danger">{err}</div>;
    if (!site) return <div className="p-4">サイトが見つかりません</div>;

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
                {/*<Button variant="outline-primary" onClick={publishAndOpen}>公開ページで確認</Button>*/}
            </div>

            {/* ページ外の操作（currentPage を使う） */}
            <div className="ms-auto d-flex gap-2 mt-2">
                <Button size="sm" onClick={()=>handleAdd("hero")}>+ Hero</Button>
                <Button size="sm" onClick={()=>handleAdd("features")}>+ Features</Button>
                <Button size="sm" onClick={()=>handleAdd("cta")}>+ CTA</Button>
                <Button size="sm" variant="outline-primary"
                        onClick={()=> currentPage && openPublic(site.slug, currentPage.path)}>
                    公開でこのページを開く
                </Button>
                <Button size="sm" variant="outline-secondary"
                        onClick={()=>addPage("/new","New Page")}>+ Page</Button>
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

                            {b.type==="hero"     && <HeroEditor     siteId={p.id} onSave={(next)=>updateBlock(b, next)} />}
                            {b.type==="features" && <FeaturesEditor data={b.data} onSave={(d)=>updateBlock(b, d)} />}
                            {b.type==="cta"      && <CtaEditor      data={b.data} onSave={(d)=>updateBlock(b, d)} />}
                        </div>
                    ))}
                </div>
            ))}
        </div>
    );
}
