import React, { useEffect, useState } from "react";
import { api } from "@/helpers/api";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import type {FeatureCard} from "../model/types.ts";
export function Features(features:FeatureCard[]){
    console.log(features)
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


    return(
        <div className="max-w-6xl mx-auto p-6">
            <header className="flex items-center justify-between py-4">
                <div className="font-semibold text-lg">{s["site.name"] ?? "Regal"}</div>
                <nav className="space-x-4 text-sm">
                    <Link to="/">ホーム</Link>
                    <Link to=":tenant/reserve">予約</Link>
                    <Link to="s/:slug">TenantHome</Link>
                    <Link to="Offices">Offices</Link>
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
                {/*{s["hero.image"] && <img src={s["hero.image"]} className="mx-auto mt-10 rounded-2xl shadow" />}*/}
            </section>


        </div>
    )
}