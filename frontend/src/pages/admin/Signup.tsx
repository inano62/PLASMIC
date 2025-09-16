// src/pages/admin/Signup.tsx
import { useState } from "react";
import api, { postWeb } from "@/lib/api";

export default function Signup() {
    const [name, setName]       = useState("");
    const [email, setEmail]     = useState("");
    const [password, setPass]   = useState("");
    const [err, setErr]         = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    async function onSubmit(e: React.FormEvent) {
        e.preventDefault();
        setErr(null);
        setLoading(true);
        try {
            // 1) 会員作成（webルート）
            await postWeb("/register", {
                name, email,
                password,
                password_confirmation: password,
            });

            // 2) ログイン（webルート）
            await postWeb("/login", { email, password });

            // 3) Stripe Checkout開始（APIルート）
            const { url } = await api.checkout({ price_id: "price_xxx" });
            window.location.href = url; // Stripeのホスト決済ページへ
        } catch (e: any) {
            setErr(e?.data?.message || e?.message || "失敗しました");
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="min-h-screen grid place-items-center bg-slate-50 px-4">
            <form onSubmit={onSubmit} className="w-full max-w-sm rounded-2xl border bg-white p-6 shadow-sm">
                <h1 className="text-xl font-semibold">新規登録</h1>

                <input className="mt-4 w-full rounded-xl border px-4 py-3" placeholder="お名前"
                       value={name} onChange={e=>setName(e.target.value)} />
                <input className="mt-3 w-full rounded-xl border px-4 py-3" placeholder="メール" autoComplete="email"
                       value={email} onChange={e=>setEmail(e.target.value)} />
                <input className="mt-3 w-full rounded-xl border px-4 py-3" type="password" placeholder="パスワード" autoComplete="new-password"
                       value={password} onChange={e=>setPass(e.target.value)} />

                {err && <p className="mt-3 text-sm text-red-600">{err}</p>}

                <button className="mt-5 w-full rounded-xl bg-indigo-600 text-white py-3 hover:bg-indigo-500 disabled:opacity-60"
                        disabled={loading}>
                    {loading ? "送信中…" : "登録して決済へ"}
                </button>
            </form>
        </div>
    );
}
