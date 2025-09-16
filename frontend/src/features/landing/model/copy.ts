import type { FeatureCard, FaqItem, VerticalContent, VerticalKey } from "./types";

export const baseFeatures: FeatureCard[] = [
    { title: "予約/カレンダー", desc: "空き枠の公開、日程調整、カレンダー同期。" },
    { title: "決済/サブスク", desc: "Stripeで月額・単発・回数券。領収書発行も自動化。" },
    { title: "ビデオ面談", desc: "ブラウザだけで高品質通話。録画やチャットも拡張可能。" },
    { title: "公開HP", desc: "テンプレから数分で公開。独自ドメイン/SEO対応。" },
    { title: "通知/連絡", desc: "リマインド、既読確認、テンプレ返信。" },
    { title: "セキュリティ", desc: "権限管理、監査ログ、バックアップ、IP制限。" },
];

export const baseFaq: FaqItem[] = [
    { q: "初期費用は？", a: "不要です。月額のみで開始できます。" },
    { q: "解約はいつでも可能？", a: "はい。月単位で解約可能となっております。" },
    { q: "既存のHPや予約ツールと併用できる？", a: "できます。埋め込み or 連携で段階的に移行できます。" },
];

export const PRICING = [
    { name: "ワンプラン", price: "¥19,800 / 月", points: ["HP + 管理 + ビデオ + 決済", "独自ドメイン対応", "メール/チャットサポート"] },
    { name: "年払い", price: "¥217,800 / 年", points: ["2ヶ月分お得", "優先サポート", "導入初期設定サポート"] },
    { name: "エンタープライズ", price: "お見積り", points: ["多拠点/多ブランド対応", "SLA/セキュリティ審査", "専用導入支援"] },
] as const;

export const VERTICALS: Record<VerticalKey, VerticalContent> = {
    default: {
        badge: "MULTI-INDUSTRY",
        headline: "ワンプラン、全部入り。",
        subhead: "士業・美容・教育に対応",
        heroBullets: ["予約→決済→入室URL 自動化","HP公開と管理が1つに"],
        trust: ["士業の現場で検証済み"],
        features: baseFeatures,
        faq: baseFaq,
        demoLink: "/demo",
        PublicReserve:"/Offices",
        signupLink: "/signup",
    },
     // law / beauty / edu も同様に
};
