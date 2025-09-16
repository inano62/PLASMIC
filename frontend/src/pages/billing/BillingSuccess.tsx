// src/pages/billing/BillingSuccess.tsx
import { useEffect, useState } from "react";

export default function BillingSuccess() {
    const [msg, setMsg] = useState("確認中…");

    useEffect(() => {
        const sid = new URLSearchParams(location.search).get("session_id");
        if (!sid) { setMsg("セッションIDがありません"); return; }

        fetch(`${import.meta.env.VITE_API_BASE2}/api/billing/session/${encodeURIComponent(sid)}`)
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(data => {
                setMsg(data?.status === "paid" ? "お支払いが完了しました！" : "決済は保留/未完了です。");
            })
            .catch(async (r: any) => {
                let text = "決済の検証に失敗しました";
                if (r && r.json) {
                    try {
                        const j = await r.json();
                        text += ` (${j?.error ?? j?.message ?? ""})`;
                    } catch {}
                }
                setMsg(text);
            });
    }, []); // ← useEffect をここで閉じる

    return (
        <div className="min-h-screen grid place-items-center p-6">
            <div className="max-w-md w-full rounded-2xl border p-6">
                <h1 className="text-xl font-semibold mb-3">決済結果</h1>
                <p>{msg}</p>
                <a className="text-indigo-600 underline mt-4 inline-block" href="/admin/site">
                    管理画面へ戻る
                </a>
            </div>
        </div>
    );
}
