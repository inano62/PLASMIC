import  { useEffect, useMemo, useState } from "react";

/**
 * PLASMIC Marketing / Vertical LP (single-file)
 * - Tailwind assumed
 * - Works as the main PLASMIC page (general) AND as a vertical LP (e.g., law, beauty, edu)
 * - Choose vertical via:
 *     1) Query:   ?v=law | beauty | edu | default
 *     2) Path:    /law, /beauty, /edu (detects from location.pathname)
 *     3) Prop:    <PlasmicLanding vertical="law" />
 */

// ---- types ----
type VerticalKey = "default" | "law" | "beauty" | "edu";
interface VerticalContent {
    badge: string;
    headline: string;
    subhead: string;
    heroBullets: string[];
    trust: string[];
    features: FeatureCard[];
    faq: FaqItem[];
    demoLink: string;
    signupLink: string;
}
interface FeatureCard { title: string; desc: string }
interface FaqItem { q: string; a: string }

const isVertical = (k: string): k is VerticalKey => ["default","law","beauty","edu"].includes(k as any);

// ---- shared copy (先に定義：TDZを避ける) ----
const baseFeatures: FeatureCard[] = [
    { title: "予約/カレンダー", desc: "空き枠の公開、日程調整、カレンダー同期。" },
    { title: "決済/サブスク", desc: "Stripeで月額・単発・回数券。領収書発行も自動化。" },
    { title: "ビデオ面談", desc: "ブラウザだけで高品質通話。録画やチャットも拡張可能。" },
    { title: "公開HP", desc: "テンプレから数分で公開。独自ドメイン/SEO対応。" },
    { title: "通知/連絡", desc: "リマインド、既読確認、テンプレ返信。" },
    { title: "セキュリティ", desc: "権限管理、監査ログ、バックアップ、IP制限。" },
];

const baseFaq: FaqItem[] = [
    { q: "初期費用は？", a: "不要です。月額のみで開始できます。" },
    { q: "解約はいつでも可能？", a: "はい。月単位での解約が可能です。データのエクスポートも提供します。" },
    { q: "既存のHPや予約ツールと併用できる？", a: "できます。埋め込み or 連携で段階的に移行できます。" },
];

const PRICING: { name: string; price: string; points: string[] }[] = [
    { name: "ワンプラン", price: "¥20,000 / 月", points: ["HP + 管理 + ビデオ + 決済", "独自ドメイン対応", "メール/チャットサポート"] },
    { name: "年払い", price: "¥220,000 / 年", points: ["2ヶ月分お得", "優先サポート", "導入初期設定サポート"] },
    { name: "エンタープライズ", price: "お見積り", points: ["多拠点/多ブランド対応", "SLA/セキュリティ審査", "専用導入支援"] },
];

// ---- vertical content ----
const VERTICALS: Record<VerticalKey, VerticalContent> = {
    default: {
        badge: "MULTI-INDUSTRY",
        headline: "ワンプラン、全部入り。予約・支払い・ビデオ・HPをひとつに",
        subhead:
            "PLASMIC は士業・美容・教育まで、専門性の高い現場に必要な機能をオールインワンで提供します。",
        heroBullets: ["予約→決済→入室URL 自動化","HP公開と管理が1つに","Stripe月謝/顧問料に対応","LiveKitで高品質ビデオ"],
        trust: ["士業の現場で検証済み","医療・教育の導入事例","サクラVPSで国内運用可"],
        features: baseFeatures,
        faq: baseFaq,
        demoLink: "/demo",
        signupLink: "/signup",
    },
    law: {
        badge: "士業向け",
        headline: "士業のためのオンライン面談と顧問運用を、これ1つで",
        subhead: "予約→委任前ヒアリング→ビデオ面談→請求・入金までを一気通貫。依頼者とのやり取りを漏れなく可視化。",
        heroBullets: ["依頼者の身元確認ログ","案件ごとの記録・添付","顧問料の自動課金","専用HPテンプレ付き"],
        trust: ["行政書士/司法書士 事務所で導入","顧客はスマホだけで完結"],
        features: baseFeatures,
        faq: [
            { q: "既存HPはそのまま使える？", a: "はい。サブドメイン連携または埋め込みウィジェットで併用可能です。" },
            { q: "顧問料の自動課金に対応？", a: "Stripeで月額・年額に対応。領収書自動発行も可能です。" },
            { q: "セキュリティは？", a: "TLS/HTTPS、権限管理、監査ログ、定期バックアップを提供します。" },
        ],
        demoLink: "/demo?vertical=law",
        signupLink: "/signup?plan=pro&v=law",
    },
    beauty: {
        badge: "美容/クリニック向け",
        headline: "予約・回数券・ビデオ相談をワンプラットフォームで",
        subhead: "来店予約やオンラインカウンセリング、回数券の管理まで。現場の負担を減らしリピート率を上げます。",
        heroBullets: ["LINE代替の一斉連絡","Before/Afterの安全共有","回数券/サブスク決済","店舗HPテンプレ付き"],
        trust: ["小規模店舗から複数院まで","スマホで全オペ完結"],
        features: baseFeatures,
        faq: baseFaq,
        demoLink: "/demo?vertical=beauty",
        signupLink: "/signup?plan=pro&v=beauty",
    },
    edu: {
        badge: "教育/習い事向け",
        headline: "月謝回収・出欠・保護者連絡・オンライン授業をひとまとめ",
        subhead: "個別指導・家庭教師・習い事の面倒を1つの画面で。保護者の安心と運営の効率化を両立します。",
        heroBullets: ["月謝の自動課金","出欠と振替管理","保護者アプリ不要の連絡","オンライン授業/面談"],
        trust: ["小規模教室で導入容易","スマホで保護者に可視化"],
        features: baseFeatures,
        faq: [
            { q: "月謝の未納対策は？", a: "自動請求/自動再試行/督促メールで回収率を改善します。" },
            { q: "保護者との連絡は？", a: "お知らせ機能で一斉送信＋既読確認。LINE併用も可能です。" },
            { q: "オンライン授業の品質は？", a: "LiveKitベースでブラウザだけで高品質なビデオが使えます。" },
        ],
        demoLink: "/demo?vertical=edu",
        signupLink: "/signup?plan=pro&v=edu",
    },
};

export default function PlasmicLanding(props: { vertical?: VerticalKey }) {
    const [urlVertical, setUrlVertical] = useState<VerticalKey | undefined>();

    useEffect(() => {
        try {
            const url = new URL(window.location.href);
            const q = (url.searchParams.get("v") || "").toLowerCase();
            const path = (url.pathname || "/").replace(/^\/+|\/+$/g, "");
            const pathKey = path.split("/")[0] as VerticalKey;
            if (isVertical(q)) setUrlVertical(q);
            else if (isVertical(pathKey)) setUrlVertical(pathKey);
        } catch (e) {
            // SSR等では無視
        }
    }, []);

    const vertical = props.vertical || urlVertical || "default";
    const V = useMemo(() => VERTICALS[vertical], [vertical]);

    return (
        <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white text-slate-900">
            {/* Top bar */}
            <header className="sticky top-0 z-40 backdrop-blur bg-white/70 border-b border-slate-200">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="h-8 w-8 rounded-2xl bg-gradient-to-br from-fuchsia-500 to-indigo-500"/>
                        <span className="font-semibold tracking-tight">PLASMIC</span>
                        {vertical !== "default" && (
                            <span className="ml-2 text-xs rounded-full px-2 py-0.5 bg-slate-100 border border-slate-200">{V.badge}</span>
                        )}
                    </div>
                    <nav className="hidden md:flex items-center gap-6 text-sm">
                        <a href="#features" className="hover:text-slate-600">機能</a>
                        <a href="#pricing" className="hover:text-slate-600">料金</a>
                        <a href="#security" className="hover:text-slate-600">セキュリティ</a>
                        <a href="#faq" className="hover:text-slate-600">FAQ</a>
                    </nav>
                    <div className="flex items-center gap-3">
                        <a href="/admin" className="text-sm px-3 py-1.5 rounded-xl border border-slate-300 hover:bg-slate-50">ログイン</a>
                        <a href="#cta" className="text-sm px-3 py-1.5 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">無料で試す</a>
                    </div>
                </div>
            </header>

            {/* Hero */}
            <section className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-16 pb-12">
                <div className="grid lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <h1 className="text-4xl md:text-5xl font-extrabold leading-tight tracking-tight">
                            {V.headline}
                        </h1>
                        <p className="mt-5 text-lg text-slate-600">{V.subhead}</p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <a id="cta" href={V.demoLink} className="px-5 py-3 rounded-2xl bg-indigo-600 text-white hover:bg-indigo-700">デモを見る</a>
                            <a href="#pricing" className="px-5 py-3 rounded-2xl border border-slate-300 hover:bg-slate-50">料金を見る</a>
                        </div>
                        <div className="mt-6 flex flex-wrap gap-2">
                            {V.trust.map((t) => (
                                <span key={t} className="text-xs px-2 py-1 rounded-full bg-slate-100 border border-slate-200">{t}</span>
                            ))}
                        </div>
                    </div>
                    <div className="relative">
                        <div className="absolute -inset-4 rounded-3xl bg-gradient-to-tr from-indigo-200/60 to-fuchsia-200/60 blur-2xl"/>
                        <div className="relative rounded-3xl border border-slate-200 shadow-xl bg-white p-4">
                            {/* Replace with your live product screenshot */}
                            <div className="aspect-video w-full rounded-2xl bg-slate-100 grid place-items-center text-slate-500">
                                <span>Product Preview</span>
                            </div>
                            <ul className="mt-4 grid grid-cols-2 gap-2 text-sm">
                                {V.heroBullets.map((b) => (
                                    <li key={b} className="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200">{b}</li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            {/* Features */}
            <section id="features" className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
                <h2 className="text-2xl font-bold">主要機能</h2>
                <p className="text-slate-600 mt-2">ワンプラン・全部入り。面談予約〜決済〜ビデオ〜公開HPまで。</p>
                <div className="mt-8 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {V.features.map((f) => (
                        <div key={f.title} className="rounded-2xl border border-slate-200 p-5 bg-white shadow-sm">
                            <div className="text-sm font-semibold">{f.title}</div>
                            <div className="text-slate-600 text-sm mt-2">{f.desc}</div>
                        </div>
                    ))}
                </div>
            </section>

            {/* Pricing */}
            <section id="pricing" className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
                <h2 className="text-2xl font-bold">料金（ワンプラン）</h2>
                <p className="text-slate-600 mt-2">HP＋管理画面＋ビデオチャット＋決済。隠れ費用はありません。</p>
                <div className="mt-8 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {PRICING.map((p) => (
                        <div key={p.name} className="rounded-2xl border border-slate-200 p-6 bg-white shadow-sm flex flex-col">
                            <div className="text-sm font-semibold">{p.name}</div>
                            <div className="mt-4 text-3xl font-extrabold">{p.price}</div>
                            <ul className="mt-4 space-y-2 text-sm text-slate-600">
                                {p.points.map((pt) => (<li key={pt}>• {pt}</li>))}
                            </ul>
                            <a href={V.signupLink} className="mt-6 px-4 py-2 rounded-xl bg-indigo-600 text-white text-center hover:bg-indigo-700">申し込む</a>
                        </div>
                    ))}
                </div>
                <p className="text-xs text-slate-500 mt-4">※ カスタム要件がある場合はご相談ください。</p>
            </section>

            {/* Security / Compliance */}
            <section id="security" className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
                <h2 className="text-2xl font-bold">セキュリティ・法令対応</h2>
                <div className="mt-6 grid md:grid-cols-2 gap-6">
                    <div className="rounded-2xl border border-slate-200 p-6 bg-white">
                        <div className="font-semibold">データ保護</div>
                        <p className="text-sm text-slate-600 mt-2">TLS/HTTPS、権限分離、監査ログ、バックアップ。個人情報は最小限で管理。</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 p-6 bg-white">
                        <div className="font-semibold">決済</div>
                        <p className="text-sm text-slate-600 mt-2">Stripeによる安全な月額課金。クレジット情報は弊社サーバーに保存しません。</p>
                    </div>
                </div>
            </section>

            {/* FAQ */}
            <section id="faq" className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-12">
                <h2 className="text-2xl font-bold">よくある質問</h2>
                <div className="mt-6 space-y-4">
                    {V.faq.map((q) => (
                        <details key={q.q} className="rounded-2xl border border-slate-200 p-4 bg-white">
                            <summary className="font-medium cursor-pointer">{q.q}</summary>
                            <p className="text-sm text-slate-600 mt-2">{q.a}</p>
                        </details>
                    ))}
                </div>
            </section>

            {/* Footer */}
            <footer className="border-t border-slate-200 py-10">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div className="text-sm text-slate-500">© {new Date().getFullYear()} PLASMIC</div>
                    <div className="text-xs text-slate-400">このページは縦展開対応テンプレートです：/law, /beauty, /edu または ?v=law 等で文言が自動最適化されます。</div>
                </div>
            </footer>
        </div>
    );
}
