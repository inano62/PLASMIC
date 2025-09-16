// src/pages/TenantHome.tsx
import { useTenantRequired } from "@/features/landing/hooks/useTenantRequired";
export default function TenantHome() {
    const { tenant, loading } = useTenantRequired();
    if (loading) return <div className="p-8 text-center">読み込み中…</div>;
    if (!tenant) return null;

    return (
        <div className="mx-auto max-w-5xl p-6">
            <h1 className="text-2xl font-bold mb-4">{tenant.display_name}</h1>
            <p className="text-slate-600 mb-6">事務所の紹介文など…</p>
            <a className="btn btn-primary" href={`/s/${tenant.slug}/reserve`}>予約へ進む</a>
        </div>
    );
}
