// src/pages/billing/Success.tsx
import { useSearchParams } from "react-router-dom";
export default function Success() {
    const [sp] = useSearchParams();
    const sid = sp.get("session_id");
    return (
        <div className="min-h-screen grid place-items-center">
            <div className="max-w-md p-6 bg-white rounded-2xl shadow">
                <h1 className="text-xl font-semibold">決済が完了しました</h1>
                <p className="mt-2 text-slate-600">登録いただいたメールとパスワードでログインしてください。</p>
                <a className="mt-4 inline-block rounded-xl bg-indigo-600 text-white px-4 py-2" href="/admin/login">
                    ログインへ
                </a>
                <p className="mt-3 text-xs text-slate-400">Session: {sid}</p>
            </div>
        </div>
    );
}

// src/pages/billing/Cancel.tsx
export default function Cancel() {
    return (
        <div className="min-h-screen grid place-items-center">
            <div className="max-w-md p-6 bg-white rounded-2xl shadow">
                <h1 className="text-xl font-semibold">決済をキャンセルしました</h1>
                <a className="mt-4 inline-block rounded-xl bg-slate-700 text-white px-4 py-2" href="/signup">
                    申し込みに戻る
                </a>
            </div>
        </div>
    );
}
