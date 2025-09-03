import { useNavigate } from "react-router-dom";
import { useState } from "react";
import { ADMIN_TOKEN_KEY } from "../../lib/auth";


export default function AdminLogin(){
    const nav = useNavigate();
    const [email, setEmail] = useState("");
    const [pass, setPass] = useState("");

    function submit(e: React.FormEvent) {
        e.preventDefault();
        if (pass === "admin") {
            localStorage.setItem(ADMIN_TOKEN_KEY, "dev");
            nav("/admin", { replace: true });   // ← replace
        } else {
            alert("パスワードが違います（開発中は 'admin'）");
        }
    }
    return (
        <div className="min-h-screen grid place-items-center bg-slate-50 px-4">
            <form onSubmit={submit} className="w-full max-w-sm rounded-2xl border bg-white p-6 shadow-sm">
                <h1 className="text-xl font-semibold">Adminログイン</h1>
                <input className="mt-4 w-full rounded-xl border px-4 py-3" placeholder="メール" value={email} onChange={e=>setEmail(e.target.value)} />
                <input className="mt-3 w-full rounded-xl border px-4 py-3" type="password" placeholder="パスワード" value={pass} onChange={e=>setPass(e.target.value)} />
                <button className="mt-5 w-full rounded-xl bg-indigo-600 text-white py-3 hover:bg-indigo-500">ログイン</button>
            </form>
        </div>
    );
}