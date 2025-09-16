import { useEffect, useState } from "react";
import type { VerticalKey } from "../model/types";

const isVertical = (k: string): k is VerticalKey =>
    ["default","law","beauty","edu"].includes(k as any);

export function useVertical(prop?: VerticalKey) {
    const [fromUrl, setFromUrl] = useState<VerticalKey | undefined>();

    useEffect(() => {
        try {
            const url = new URL(window.location.href);
            const q = (url.searchParams.get("v") || "").toLowerCase();
            const pathKey = (url.pathname.replace(/^\/+|\/+$/g,"").split("/")[0] || "") as VerticalKey;
            if (isVertical(q)) setFromUrl(q);
            else if (isVertical(pathKey)) setFromUrl(pathKey);
        } catch { /* SSR 対応で無視 */ }
    }, []);

    return prop || fromUrl || "default";
}
