// src/pages/admin/site/SiteList.tsx
import { useEffect, useState } from "react";
import { api } from "../../../lib/api";
import { useNavigate } from "react-router-dom";

export default function SiteList() {
    const [sites, setSites] = useState<any[] | null>(null);
    const navigate = useNavigate();

    useEffect(() => {
        api.get("/sites/my")
            .then(({ data }) => setSites(data))
            .catch(() => setSites([]));
    }, []);

    if (sites === null) return null; // ローディング
    if (sites.length === 0) {
        return (
            <div className="empty">
                <h2>サイトが見つかりません</h2>
                <p>最初のサイトを作成しましょう。テンプレートから始めると5分で公開できます。</p>
                <button className="btn btn-primary" onClick={() => navigate("/admin/site/new")}>
                    最初のサイトを作成
                </button>
            </div>
        );
    }

    return (
        <ul>{sites.map(s => <li key={s.id}>{s.title}</li>)}</ul>
    );
}
