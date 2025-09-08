// src/pages/admin/Site.tsx
import { useEffect, useState } from "react";
import API from "../../lib/api"; // default { get, post, getJson, postJson }

type Status = { allowed: boolean; account_type: string; price_id?: string };

export default function AdminSite() {
    const [status, setStatus] = useState<Status | null>(null);
    const [err, setErr] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        API.getJson<Status>("/sitebuilder/status")
            .then((s) => setStatus(s))
            .catch((e) => setErr(String(e)))
            .finally(() => setLoading(false));
    }, []);

    if (loading) return <div className="container py-5">読み込み中…</div>;
    if (err) return <div className="container py-5 alert alert-danger">エラー: {err}</div>;

    // 未課金 → ペイウォール
    if (!status!.allowed) {
        const onCheckout = async () => {
            try {
                const r = await API.postJson<{ url: string }>("/sitebuilder/checkout", {});
                location.href = r.url; // Stripe の Checkout へ
            } catch (e: any) {
                alert("決済ページの作成に失敗しました");
                console.error(e);
            }
        };

        return (
            <div className="container py-5" style={{ maxWidth: 720 }}>
                <h2 className="mb-3">サイトビルダー</h2>
                <div className="alert alert-info">
                    この機能をご利用いただくには「プロ（サイト作成）」のお支払いが必要です。
                </div>
                <ul className="mb-3">
                    <li>独自ページ作成（Hero/Features/CTA 等）</li>
                    <li>予約フォームへの導線ボタン</li>
                    <li>公開／プレビュー、後から編集OK</li>
                </ul>
                <button className="btn btn-primary btn-lg" onClick={onCheckout}>
                    19,800円で今すぐ申し込む
                </button>
            </div>
        );
    }

    // 課金済み → 既存のビルダーを表示
    return <SiteBuilder />; // ← いまのビルダーコンポーネントをここでレンダリング
}
