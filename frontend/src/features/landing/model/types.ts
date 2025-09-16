// 型はここに一本化
export type VerticalKey = "default" | "law" | "beauty" | "edu";

export interface FeatureCard { title: string; desc: string }
export interface FaqItem { q: string; a: string }  // ← これを必ず export

export interface VerticalContent {
    badge: string;
    headline: string;
    subhead: string;
    heroBullets: string[];
    trust: string[];
    features: FeatureCard[];
    faq: FaqItem[];
    demoLink: string;
    PublicReserve: string;
    signupLink: string;
}
