// src/pages/admin/AdminLogin.tsx
import { useNavigate } from "react-router-dom";
import { useState,createContext, useContext,  } from "react";
import {postWeb} from "../../lib/api"; // 既存の api.ts（/sanctum/csrf-cookie 済み）

export default function Login() {
    const nav = useNavigate();
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    type User = { id:number; name:string; email:string; role?:string } | null;
    const AuthContext = createContext<{ user:User; setUser:(u:User)=>void }>({ user:null, setUser:()=>{} });
    function useAuth() { return useContext(AuthContext); }
    const {setUser} = useAuth();

    async function submit(e: React.FormEvent) {
        e.preventDefault();
        setError(null);
        setLoading(true);
        try {
            // 1) web直でログイン（Sanctum Cookie）
            await postWeb("/login", { email, password }); // 204想定
            // 2) 認証済みユーザ取得（失敗したら catch）
            const me = await postWeb<{ id: number; name: string; email: string;role?:string }>("/user");
            setUser(me);
            if (me.role !== "admin") {
                setError("管理者権限がありません");
                return;
            }
            // 3) とりあえず管理画面へ
            nav("/admin/site", { replace: true });
        } catch (err: unknown) {
            let msg = "ログインに失敗しました。";
            const e = err as { status?: number };
            if (e?.status === 422) msg = "メールまたはパスワードが違います。";
            if (e?.status === 401) msg = "認証に失敗しました。";
            if (e?.status === 419) msg = "CSRFが失効しました。ページを再読み込みしてください。";
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
