import {JSX, useEffect, useMemo, useState} from "react";
import {useParams, useLocation, Link, useLoaderData} from "react-router-dom";
import type { Block } from "./blocks/types";
import Hero from "./blocks/Hero";
import Features from "./blocks/Features";
import Cta from "./blocks/Cta";
import HeaderBlock from "./blocks/HeaderBlock.tsx";
import ImageBlock from "./blocks/ImageBlock.tsx";
import MediaCard from "./blocks/MediaCard";
import Gallery from "./blocks/Gallery";
import {api} from "../lib/api.ts";

type ApiResp = {
    site: { title: string; slug: string };
    page: { title: string; path: string; blocks: Block[] };
    nav: { title: string; path: string }[];
};
type Office = { id:number; name:string };
type Site = { id:number; slug:string; title:string; office?: Office };

const RENDERERS: Record<string, (p: { data: any }) => JSX.Element> = {
    hero: (p) => <Hero data={p.data} />,
    features: (p) => <Features data={p.data} />,
    cta: (p) => <Cta data={p.data} />,
    header: (p) => <HeaderBlock data={p.data} />,
    image: (p:any) => <ImageBlock data={p.data} />,
    mediacard: (p:any) => <MediaCard data={p.data} />,
    gallery: (p:any) => <Gallery data={p.data} />
};

export default function PublicSite() {
    const site = useLoaderData() as Site;
    const { slug } = useParams();
    const loc = useLocation();

    const path = useMemo(() => {
        const raw = loc.pathname.replace(/^\/s\/[^/]+/, "") || "/";
        return raw.startsWith("/") ? raw : `/${raw}`;
    }, [loc.pathname]);

    const reserveHref = slug
        ? `/reserve?slug=${encodeURIComponent(slug)}`
        : undefined;

    const [data, setData] = useState<ApiResp | null>(null);
    const [err, setErr] = useState<string | null>(null);

    useEffect(() => {
        let ignore = false;
        (async () => {
            try {
                setErr(null);
                setData(null);
                const res = await fetch(`${import.meta.env.VITE_API_ORIGIN ?? "http://localhost:8000"}/api/public/sites/${slug}/page?path=${encodeURIComponent(path)}`);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const json: ApiResp = await res.json();
                if (!ignore) {
                    setData(json);
                    document.title = `${json.page.title} | ${json.site.title}`;
                }
            } catch (e: any) {
                if (!ignore) setErr(e.message || "Error");
            }
        })();
        return () => {
            ignore = true;
        };
    }, [slug, path]);

    if (err) return <div style={{ padding: 24 }}>読み込み失敗: {err}</div>;
    if (!data) return <div style={{ padding: 24 }}>読み込み中…</div>;
    const heroBlock = data.page.blocks.find(b => String(b.type).toLowerCase() === "hero");
    const heroImgUrl: string | undefined = heroBlock?.data?.imgUrl;

    return (
        <div className="relative isolate">
            {heroImgUrl && (
                <div className="absolute inset-x-0 top-0 z-0 h-[260px] md:h-[360px]">
                    <img src={heroImgUrl} alt="" className="w-full h-full object-cover" />
                    {/* 上を少し白くしてテキストを読みやすく */}
                    <div className="absolute inset-x-0 top-0 h-16 bg-gradient-to-b from-white/70 to-transparent" />
                    {/* 下を白にフェード */}
                    <div className="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-b from-transparent to-white" />
                </div>
            )}
            <header className="container py-4 d-flex gap-3 relative z-10">

                <Link to={`/s/${data.site.slug}/`} className="fw-bold text-decoration-none fs-5">
                    {data.site.title}
                </Link>
                <nav className="ms-auto d-flex gap-3">
                    {(data.nav ?? []).map((n) => (
                        <Link key={n.path} to={`/s/${data.site.slug}${n.path}`} className="link-secondary">
                            {n.title}
                        </Link>
                    ))}
                </nav>
                {reserveHref && (
                    <Link className="btn btn-success btn-sm" to={reserveHref}>
                        チャットを予約する
                    </Link>
                )}
            </header>

            <main className="container py-4 d-grid gap-5 relative z-10">
                {data.page.blocks
                    .slice()
                    .sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
                    .map((b) => {
                        const type = String(b.type || '').toLowerCase();    // ★小文字化
                        const Comp = RENDERERS[type];
                        const key  = (b as any).id ?? `${type}-${b.sort ?? 0}`; // ★安定 key
                        return (
                            <div key={key}>
                                {Comp ? (
                                    <Comp data={b.data} />
                                ) : (
                                    <div className="border rounded-4 p-4">
                                        <div className="fw-semibold mb-2">{b.type}</div>
                                        <pre className="mb-0">{JSON.stringify(b.data, null, 2)}</pre>
                                    </div>
                                )}
                            </div>
                        );
                    })}
            </main>

            <footer className="container py-4 border-top text-muted small">
                © {new Date().getFullYear()} {data.site.title}
            </footer>
        </div>
    );
}
