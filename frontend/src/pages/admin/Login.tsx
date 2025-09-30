// src/pages/admin/Login.tsx
import { useNavigate } from "react-router-dom";
import React, { useState} from "react";
import { useAuth } from "../../contexts/auth";

export default function Login() {
    const nav = useNavigate();
    const { login, refresh } = useAuth();
    const [email, setEmail] = useState("jemesouviens1@email.com"); // 開発中はデフォルト入れておくと楽
    const [password, setPassword] = useState("password");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function submit(e: React.FormEvent) {
        e.preventDefault();
        setError(null); setLoading(true);
        try {
            await login(email, password);   // ← /api/auth/token
            await refresh();                // ← /api/whoami
            nav("/admin/site", { replace: true });
        } catch (err: any) {
            const s = err?.response?.status;
            if (s === 401) setError("メールまたはパスワードが違います。");
            else setError(err?.response?.data?.message || "ログインに失敗しました。");
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
