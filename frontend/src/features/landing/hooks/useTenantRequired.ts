// src/features/landing/hooks/useTenantRequired.ts
import { useEffect, useState } from "react";
import { useNavigate, useParams, useSearchParams } from "react-router-dom";
import API from "@/lib/api";

type Tenant = { id:number; slug:string; display_name:string; home_url?:string };

export function useTenantRequired() {
    const navigate = useNavigate();
    const { slug: slugFromPath } = useParams();
    const [sp] = useSearchParams();
    const slug = slugFromPath || sp.get("slug") || "";
    const [tenant, setTenant] = useState<Tenant | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        (async () => {
            try {
                if (!slug) throw new Error("no-slug");
                const res = await API.get(`/public/tenants/resolve?slug=${encodeURIComponent(slug)}`);
                if (!res.ok) throw new Error("resolve-failed");
                const t = await res.json();
                setTenant(t);
            } catch {
                navigate("/offices", { replace: true, state: { msg: "事務所を選んでください" } });
            } finally {
                setLoading(false);
            }
        })();
    }, [slug, navigate]);

    return { tenant, loading };
}
