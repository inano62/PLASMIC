// lib/site/types.ts
export type SiteTheme = { primary: string; accent: string; font?: string };
export type Block =
    | { type: "hero"; title: string; subtitle?: string; ctaLabel?: string; }
    | { type: "features"; items: { icon?: string; title: string; text: string }[] }
    | { type: "cta"; title: string; button: string; link: string };

export type SiteData = {
    slug: string;                 // 例: "judist-sakai"
    officeName: string;           // 事務所名
    theme: SiteTheme;
    blocks: Block[];
};
