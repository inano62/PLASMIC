import { useEffect, useMemo, useState } from "react";
import { useParams, useLocation, Link } from "react-router-dom";
import type { Block } from "./blocks/types";
import Hero from "./blocks/Hero";
import Features from "./blocks/Features";
import Cta from "./blocks/Cta";

type ApiResp = {
    site: { title: string; slug: string };
    page: { title: string; path: string; blocks: Block[] };
    nav: { title: string; path: string }[];
};

const RENDERERS: Record<string, (p: { data: any }) => JSX.Element> = {
    hero: (p) => <Hero data={p.data} />,
    features: (p) => <Features data={p.data} />,
    cta: (p) => <Cta data={p.data} />,
};

export default function PublicSite() {
    const { slug } = useParams();
    const loc = useLocation();

    const path = useMemo(() => {
        const raw = loc.pathname.replace(/^\/s\/[^/]+/, "") || "/";
        return raw.startsWith("/") ? raw : `/${raw}`;
    }, [loc.pathname]);

    const [data, setData] = useState<ApiResp | null>(null);
    const [err, setErr] = useState<string | null>(null);

    useEffect(() => {
        let ignore = false;
        (async () => {
            try {
                setErr(null);
                setData(null);
                const res = await fetch(`/api/public/sites/${slug}/page?path=${encodeURIComponent(path)}`);
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
console.log(data)
    return (
        <div>
            <header className="container py-4 d-flex gap-3">
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
            </header>

            <main className="container py-4 d-grid gap-5">
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
