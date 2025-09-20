// Thanks.tsx（例）
import { useEffect } from "react";
import API from "../../src/lib/api";
import { useNavigate } from "react-router-dom";

export default function Thanks() {
    const nav = useNavigate();
    useEffect(() => {
        const id = new URLSearchParams(location.search).get("session_id");
        if (!id) return;
        (async () => {
            await API.get(`/api/billing/thanks?session_id=${id}`); // ← ここで entitled=true にする
            nav("/admin/site", { replace: true });
        })();
    }, []);
    return <div className="p-8">決済の確認中です…</div>;
}