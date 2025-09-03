// lib/site/storage.ts
import type { SiteData } from "./types";

const KEY = (slug: string) => `site:${slug}`;

export function loadSite(slug: string): SiteData | null {
    const raw = localStorage.getItem(KEY(slug));
    return raw ? JSON.parse(raw) as SiteData : null;
}
export function saveSite(data: SiteData) {
    localStorage.setItem(KEY(data.slug), JSON.stringify(data));
}
