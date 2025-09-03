import { create } from "./tinyStore";


type SiteSettings = {
    brandName: string;
    logoUrl: string;
    heroTitle: string;
    heroSubtitle: string;
    heroImageUrl: string;
    accentHex: string; // 例: #4f46e5
};


const DEFAULTS: SiteSettings = {
    brandName: "Regal",
    logoUrl: "",
    heroTitle: "ワンクリックで、オンライン面談",
    heroSubtitle: "予約→決済→入室URL発行をシンプルに。LiveKitベースで高品質ビデオ。",
    heroImageUrl: "",
    accentHex: "#4f46e5",
};


function load(): SiteSettings {
    try { const raw = localStorage.getItem("_site_settings"); if(raw) return {...DEFAULTS, ...JSON.parse(raw)}; } catch{}
    return DEFAULTS;
}


function persist(s: SiteSettings){ localStorage.setItem("_site_settings", JSON.stringify(s)); }


export function useSiteSettings(){
    const store = create<SiteSettings & { overwrite: (s: SiteSettings)=>void; export: ()=>SiteSettings }>(
        {...load(),
            overwrite: (s)=>{ persist(s); Object.assign(store, s); document.documentElement.style.setProperty('--accent', s.accentHex || '#4f46e5'); },
            export: ()=> ({brandName: store.brandName, logoUrl: store.logoUrl, heroTitle: store.heroTitle, heroSubtitle: store.heroSubtitle, heroImageUrl: store.heroImageUrl, accentHex: store.accentHex}),
        }
    );
// 初期ロード時に色を反映
    document.documentElement.style.setProperty('--accent', store.accentHex || '#4f46e5');
    return store;
}

