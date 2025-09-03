import { useEffect, useState } from "react";
import { api } from "@/helpers/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Link } from "react-router-dom";

export default function Home(){
    const [s, setS] = useState<any>({});
    const [tenants, setTenants] = useState<any[]>([]);
    const [slots, setSlots] = useState<any[]>([]);
    const [tenantId, setTenantId] = useState<number>();
    useEffect(()=>{ (async()=>{
        setTenants(await api("/api/public/tenants"));
    })(); },[]);
    useEffect(()=>{ if(!tenantId) return; (async()=>{
        const from = new Date().toISOString();
        const to = new Date(Date.now()+7*86400000).toISOString();
        setSlots(await api(`/api/public/tenants/${tenantId}/slots?from=${from}&to=${to}`));
    })(); },[tenantId]);
    useEffect(()=>{ (async()=> setS(await api("/api/settings")))(); },[]);

    const startSub = async (plan: "basic"|"pro"|"site") => {
        const { url } = await api("/api/subscriptions/start", { method:"POST", body: JSON.stringify({ plan }) });
        location.href = url; // Stripe Checkout へ遷移
    };

    return (
        <div className="max-w-6xl mx-auto p-6">
            <header className="flex items-center justify-between py-4">
                <div className="font-semibold text-lg">{s["site.name"] ?? "Regal"}</div>
                <nav className="space-x-4 text-sm">
                    <Link to="/">ホーム</Link>
                    <Link to="/reserve">予約</Link>
                    <Link to="/quick">ビデオチャット試験用</Link>
                    <Link to="/admin" className="px-3 py-1 rounded bg-primary text-primary-foreground">管理</Link>
                </nav>
            </header>

            <section className="py-16 text-center">
                <h1 className="text-4xl md:text-6xl font-bold">{s["hero.title"] ?? "ワンクリックで、オンライン面談"}</h1>
                <p className="mt-4 text-muted-foreground">{s["hero.subtitle"] ?? "予約→決済→入室URLをシンプルに"}</p>
                <div className="mt-8 flex gap-4 justify-center">
                    <Link to={s["cta.primary.href"] ?? "/reserve"}>
                        <Button>{s["cta.primary.label"] ?? "予約してはじめる"}</Button>
                    </Link>
                    <a href={s["cta.secondary.href"] ?? "#features"}>
                        <Button variant="secondary">{s["cta.secondary.label"] ?? "機能を見る"}</Button>
                    </a>
                </div>
                {s["hero.image"] && <img src={s["hero.image"]} className="mx-auto mt-10 rounded-2xl shadow" />}
            </section>

            <section id="pricing" className="py-10 grid md:grid-cols-3 gap-6">
                {[
                    { key:'basic', title:'ベーシック', price:s["pricing.basic.price"]??'3,300', note:'機能限定' },
                    { key:'pro',   title:'プロ',       price:s["pricing.pro.price"]??'8,800',   note:'ビデオ/予約 すべて' },
                    { key:'site',  title:'HP付き',     price:s["pricing.site.price"]??'9,900',  note:'LP/問い合わせ付き' },
                ].map(p=>(
                    <Card key={p.key}><CardContent className="p-6 text-center space-y-3">
                        <div className="font-semibold">{p.title}</div>
                        <div className="text-3xl font-bold">¥{p.price}<span className="text-sm text-muted-foreground">/月</span></div>
                        <div className="text-sm text-muted-foreground">{p.note}</div>
                        <Button onClick={()=>startSub(p.key as any)} className="w-full">今すぐ申し込む</Button>
                    </CardContent></Card>
                ))}
            </section>
        </div>
    );
}
