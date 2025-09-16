// src/pages/admin/AdminLogin.tsx
import { useNavigate } from "react-router-dom";
import { useState } from "react";
import API from "@/lib/api"; // 既存の api.ts（/sanctum/csrf-cookie 済み）

export default function AdminLogin() {
    const nav = useNavigate();
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function submit(e: React.FormEvent) {
        e.preventDefault();
        setError(null);
        setLoading(true);
        try {
            // Cookieベースでログイン
            await API.post("/login", { email, password });

            // (任意) 有料権限チェック → ダッシュボード or 課金ページ
            try {
                const me = await API.get("/api/me"); // ない場合は catch に落ちる
                if (me?.entitled) {
                    nav("/admin/site", { replace: true });
                } else {
                    nav("/pricing", { replace: true }); // まだ未課金なら料金ページ等へ
                }
            } catch {
                // /api/me が未実装でもまずはガードに通させる
                nav("/admin/site", { replace: true });
            }
        } catch (err: any) {
            let msg = "ログインに失敗しました。";
            if (err?.status === 422) msg = "メールまたはパスワードが違います。";
            if (err?.status === 401) msg = "認証に失敗しました。";
            if (err?.status === 419) msg = "CSRFが失効しました。ページを再読み込みしてください。";
            setError(msg);
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="min-h-screen grid place-items-center bg-slate-50 px-4">
            <form onSubmit={submit} className="w-full max-w-sm rounded-2xl border bg-white p-6 shadow-sm">
                <h1 className="text-xl font-semibold">ログイン</h1>

                <input
                    className="mt-4 w-full rounded-xl border px-4 py-3"
                    placeholder="メール"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    autoComplete="email"
                />
                <input
                    className="mt-3 w-full rounded-xl border px-4 py-3"
                    type="password"
                    placeholder="パスワード"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    autoComplete="current-password"
                />

                {error && <p className="mt-3 text-sm text-red-600">{error}</p>}

                <button
                    className="mt-5 w-full rounded-xl bg-indigo-600 text-white py-3 hover:bg-indigo-500 disabled:opacity-60"
                    disabled={loading}
                >
                    {loading ? "ログイン中…" : "ログイン"}
                </button>
            </form>
        </div>
    );
}
