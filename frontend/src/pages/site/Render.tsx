// pages/site/Render.tsx
import { useParams } from "react-router-dom";
import { loadSite } from "../../lib/site/storage";
import type { Block } from "../../lib/site/ types.ts";

function BlockView({ b }: { b: Block }) {
    switch (b.type) {
        case "hero":
            return (
                <section className="py-16">
                    <h1 className="text-4xl font-bold">{b.title}</h1>
                    {b.subtitle && <p className="mt-3 text-gray-600">{b.subtitle}</p>}
                    {b.ctaLabel && <button className="mt-6 btn btn-primary">{b.ctaLabel}</button>}
                </section>
            );
        case "features":
            return (
                <section className="py-12 grid gap-6 md:grid-cols-3">
                    {b.items.map((it, i) => (
                        <div key={i} className="p-4 rounded-xl border bg-white">
                            <div className="font-semibold">{it.title}</div>
                            <p className="text-sm text-gray-600 mt-1">{it.text}</p>
                        </div>
                    ))}
                </section>
            );
        case "cta":
            return (
                <section className="py-12 text-center">
                    <h2 className="text-2xl font-bold">{b.title}</h2>
                    <a href={b.link} className="btn btn-primary mt-4">{b.button}</a>
                </section>
            );
    }
}

export default function SiteRender() {
    const { slug = "" } = useParams();
    const site = loadSite(slug);
    if (!site) return <div className="p-6">サイトが見つかりません: {slug}</div>;

    return (
        <div className="min-h-screen bg-slate-50">
            <header className="container mx-auto px-4 py-4 flex justify-between">
                <div className="font-bold">{site.officeName}</div>
                <nav className="text-sm text-gray-500"> {/* 任意 */}</nav>
            </header>
            <main className="container mx-auto px-4">
                {site.blocks.map((b, i) => <BlockView key={i} b={b} />)}
            </main>
            <footer className="py-10 text-center text-xs text-gray-400">© {site.officeName}</footer>
        </div>
    );
}
