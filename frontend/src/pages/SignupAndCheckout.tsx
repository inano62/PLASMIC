// src/pages/admin/SignupAndCheckout.tsx
import React, { useEffect, useState } from "react";

/** ここは Vite の .env で上書き可。空なら相対パスでfetchします（Viteプロキシ想定） */
const API_BASE = (import.meta.env.VITE_API_BASE ?? "").trim();

const Logo: React.FC = () => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        width="14"
        height="16"
        viewBox="0 0 14 16"
    >
        <g fill="#E184DF">
            <path d="M127,50 L126,50 C123.238576,50 121,47.7614237 121,45 C121,42.2385763 123.238576,40 126,40 L135,40 L135,56 L133,56 L133,42 L129,42 L129,56 L127,56 L127,50 Z M127,48 L127,42 L126,42 C124.343146,42 123,43.3431458 123,45 C123,46.6568542 124.343146,48 126,48 L127,48 Z" transform="translate(-121 -40)"/>
        </g>
    </svg>
);

export default function SignupAndCheckout() {
    const [name, setName] = useState("");
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [priceId, setPriceId] = useState(
        import.meta.env.VITE_STRIPE_PRICE_ID ?? ""
    );
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [flash, setFlash] = useState<string | null>(null);

    // （任意）?success / ?canceled を拾って表示したい場合
    useEffect(() => {
        const q = new URLSearchParams(location.search);
        if (q.get("success")) setFlash("お支払いが完了しました。ご利用ありがとうございます。");
        if (q.get("canceled"))
            setFlash("お支払いをキャンセルしました。必要であれば再度お試しください。");
    }, []);

    async function submit(e: React.FormEvent) {
        e.preventDefault();
        setError(null);
        setFlash(null);
        setLoading(true);
        try {
            const url =
                (API_BASE && API_BASE !== "/") ? `${API_BASE}/signup-and-checkout`
                    : `/signup-and-checkout`;

            const res = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/json", Accept: "application/json" },
                credentials: "include",
                body: JSON.stringify({ name, email, password, price_id: priceId }),
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data?.message || `エラー: ${res.status}`);
            }

            // Stripe の Hosted Checkout へ遷移
            if (!data?.url) throw new Error("Checkout URL を取得できませんでした。");
            window.location.href = data.url;
        } catch (err: any) {
            setError(err?.message || "処理に失敗しました");
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="min-h-screen grid place-items-center bg-slate-50 px-4">
            <form
                onSubmit={submit}
                className="w-full max-w-sm rounded-2xl border bg-white p-6 shadow-sm"
            >
                {/* ヘッダー（Stripeサンプル風の説明） */}
                <section className="mb-4">
                    <div className="flex items-start gap-3">
                        <div className="mt-1">
                            <Logo />
                        </div>
                        <div>
                            <h3 className="text-base font-semibold">
                                士業務のためのオンライン相談／予約システム
                            </h3>
                            <p className="text-sm text-slate-600">
                                1カ月ごと・1アカウント：￥19,800（税込相当）
                            </p>
                        </div>
                    </div>
                </section>

                <h1 className="text-xl font-semibold">新規登録 & 決済</h1>

                <input
                    className="mt-4 w-full rounded-xl border px-4 py-3"
                    placeholder="お名前"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                />
                <input
                    className="mt-3 w-full rounded-xl border px-4 py-3"
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
                    autoComplete="new-password"
                />



                {flash && <p className="mt-3 text-sm text-emerald-600">{flash}</p>}
                {error && <p className="mt-3 text-sm text-red-600">{error}</p>}

                <button
                    className="mt-5 w-full rounded-xl bg-indigo-600 text-white py-3 hover:bg-indigo-500 disabled:opacity-60"
                    disabled={loading || !name || !email || !password || !priceId}
                >
                    {loading ? "処理中…" : "登録して決済へ"}
                </button>

                <p className="mt-4 text-center text-sm text-slate-500">
                    すでに登録済みの方は{" "}
                    <a className="text-indigo-600 underline" href="/admin/login">
                        こちら
                    </a>
                </p>
            </form>
        </div>
    );
}
