// pages/admin/site/Builder.tsx
import { useEffect, useMemo, useState } from "react";
import { saveSite, loadSite } from "../../../lib/site/storage";
import type { SiteData, Block } from "../../../lib/site/types";

const DEFAULT_SLUG = "judist-sakai";

export default function AdminSiteBuilder() {
    const [slug, setSlug] = useState(DEFAULT_SLUG);
    const [site, setSite] = useState<SiteData>(() =>
            loadSite(DEFAULT_SLUG) || {
                slug: DEFAULT_SLUG,
                officeName: "PLASMIC 法務オフィス",
                theme: { primary: "#5b5bd6", accent: "#ff66cc" },
                blocks: [
                    { type: "hero", title: "ワンプラン、全部入り。予約・決済・ビデオ・HPをひとつに", subtitle: "PLASMICは士業のための一体型プラットフォーム" },
                    { type: "features", items: [
                            { title: "予約", text: "オンラインで即時予約" },
                            { title: "決済", text: "Stripe連携で安全に" },
                            { title: "ビデオ", text: "LiveKitで高品質通話" },
                        ]},
                ],
            }
    );

    useEffect(() => { setSite(s => ({ ...s, slug })); }, [slug]);

    const addBlock = (b: Block) => setSite(s => ({ ...s, blocks: [...s.blocks, b] }));
    const updateBlock = (idx: number, b: Block) =>
        setSite(s => ({ ...s, blocks: s.blocks.map((x,i)=> i===idx ? b : x) }));
    const removeBlock = (idx: number) =>
        setSite(s => ({ ...s, blocks: s.blocks.filter((_,i)=> i!==idx) }));

    const onSave = () => { saveSite(site); alert("保存しました"); };
    const previewUrl = useMemo(()=> `/s/${site.slug}`, [site.slug]);

    return (
        <div className="container-fluid px-4">
            <h1 className="mt-4">Site Builder</h1>

            {/* ツールバー */}
            <div className="my-3 flex gap-2">
                <input className="form-control w-64" value={site.officeName}
                       onChange={e=>setSite({...site, officeName: e.target.value})} placeholder="事務所名" />
                <input className="form-control w-48" value={slug}
                       onChange={e=>setSlug(e.target.value.replace(/[^a-z0-9-]/g,''))} placeholder="slug (英小文字/数字/ハイフン)" />
                <button className="btn btn-primary" onClick={onSave}>保存</button>
                <a className="btn btn-outline-secondary" href={previewUrl} target="_blank">公開ページで確認</a>
            </div>

            <div className="grid gap-4 md:grid-cols-3">
                {/* 左：パレット */}
                <div className="p-3 rounded border bg-white">
                    <div className="fw-bold mb-2">ブロック追加</div>
                    <button className="btn btn-light w-100 mb-2"
                            onClick={()=>addBlock({ type:"hero", title:"見出し", subtitle:"サブ", ctaLabel:"問い合わせ" })}>
                        Hero
                    </button>
                    <button className="btn btn-light w-100 mb-2"
                            onClick={()=>addBlock({ type:"features", items:[{title:"項目A", text:"説明"}] })}>
                        Features
                    </button>
                    <button className="btn btn-light w-100"
                            onClick={()=>addBlock({ type:"cta", title:"無料相談はこちら", button:"予約", link:"/reserve" })}>
                        CTA
                    </button>
                </div>

                {/* 中：キャンバス（編集） */}
                <div className="p-3 rounded border bg-white md:col-span-2">
                    <div className="fw-bold mb-2">プレビュー（編集モード）</div>
                    {site.blocks.map((b, i) => (
                        <div key={i} className="border rounded p-3 mb-3">
                            <div className="d-flex justify-content-between align-items-center mb-2">
                                <div className="small text-muted">{b.type}</div>
                                <div className="d-flex gap-2">
                                    <button className="btn btn-sm btn-outline-secondary" onClick={()=>updateBlock(i, b)}>編集</button>
                                    <button className="btn btn-sm btn-outline-danger" onClick={()=>removeBlock(i)}>削除</button>
                                </div>
                            </div>

                            {/* 簡易インライン編集（最低限） */}
                            {b.type === "hero" && (
                                <>
                                    <input className="form-control mb-2" value={b.title}
                                           onChange={e=>updateBlock(i,{...b, title:e.target.value})} />
                                    <input className="form-control mb-2" value={b.subtitle||""}
                                           onChange={e=>updateBlock(i,{...b, subtitle:e.target.value})} />
                                </>
                            )}
                            {b.type === "cta" && (
                                <>
                                    <input className="form-control mb-2" value={b.title}
                                           onChange={e=>updateBlock(i,{...b, title:e.target.value})} />
                                    <div className="input-group">
                                        <input className="form-control" value={b.button}
                                               onChange={e=>updateBlock(i,{...b, button:e.target.value})} />
                                        <input className="form-control" value={b.link}
                                               onChange={e=>updateBlock(i,{...b, link:e.target.value})} />
                                    </div>
                                </>
                            )}
                            {/* 実表示 */}
                            {/* ここは pages/site/Render.tsx の BlockView を共用化してもOK */}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
