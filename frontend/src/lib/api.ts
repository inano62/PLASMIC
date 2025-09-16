// src/lib/api.ts
let TOKEN: string | null = null;

/** ===== Base URL（相対で使う） ======================================= */
const RAW_BASE = (import.meta.env.VITE_API_BASE ?? "/api").trim();
export const API_BASE = RAW_BASE.replace(/\/$/, "");            // -> "/api"
export const API_ROOT = "";                                     // 絶対URLは使わない（Vite proxy 前提）

export function setToken(token: string | null) { TOKEN = token; }

function buildUrl(path: string) {
    const p = path.startsWith("/") ? path.slice(1) : path;
    return `${API_BASE}/${p}`; // -> "/api/xxx"
}

const COMMON: RequestInit = { credentials: "include" };

/** ===== CSRF ======================================================== */
async function ensureCsrf() {
    // 相対パスで取得（Vite proxy が :8000 に中継する）
    await fetch(`/sanctum/csrf-cookie`, { credentials: "include" });
}

function xsrfFromCookie() {
    const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : "";
}

/** ===== ユーティリティ: JSON POST（相対パスのみ） ================== */
/** web直用（/register, /login など）。必ず相対パス `/xxx` を渡す */
async function postJsonRelative<T>(path: string, body: any): Promise<T> {
    await ensureCsrf();

    const headers: Record<string, string> = {
        Accept: "application/json",
        "Content-Type": "application/json",
    };

    const xsrf = xsrfFromCookie();
    if (xsrf) headers["X-XSRF-TOKEN"] = xsrf;

    const url = path.startsWith("/") ? path : `/${path}`;

    const res = await fetch(url, {
        ...COMMON,
        method: "POST",
        headers,
        body: JSON.stringify(body ?? {}),
    });

    const ct = res.headers.get("content-type") || "";
    const data = ct.includes("application/json")
        ? await res.json().catch(() => null)
        : await res.text().catch(() => null);

    if (!res.ok) {
        const err: any = new Error(typeof data === "string" ? data : data?.message || res.statusText);
        err.status = res.status;
        err.data = data;
        throw err;
    }
    return data as T;
}

/** ===== /api 配下（Sanctum 保護の JSON API） ======================= */
async function request<T>(path: string, init: RequestInit = {}) {
    const method = (init.method ?? "GET").toUpperCase();

    // 初回以降でも安全（Cookieが無ければ付与されるだけ）
    await ensureCsrf();

    const headers: Record<string, string> = {
        Accept: "application/json",
        ...(init.headers as Record<string, string> | undefined),
    };

    const isForm = init.body instanceof FormData;
    if (!isForm && init.body !== undefined) {
        headers["Content-Type"] = headers["Content-Type"] ?? "application/json";
    }

    if (TOKEN) headers["Authorization"] = `Bearer ${TOKEN}`;

    // XSRF ヘッダは付けられる時だけ付与（GET/HEAD以外）
    const xsrf = xsrfFromCookie();
    if (xsrf && method !== "GET" && method !== "HEAD") {
        headers["X-XSRF-TOKEN"] = xsrf;
    }

    const res = await fetch(buildUrl(path), { ...COMMON, ...init, headers });
    const ct = res.headers.get("content-type") || "";
    const data = ct.includes("application/json")
        ? await res.json().catch(() => null)
        : await res.text().catch(() => null);

    if (!res.ok) {
        const msg = typeof data === "string" ? data : data?.message || res.statusText;
        const err: any = new Error(msg);
        err.status = res.status;
        err.data = data;
        throw err;
    }
    return data as T;
}

/** ===== 公開 API ==================================================== */
export const api = {
    base: API_BASE,
    root: API_ROOT,
    setToken,

    // 汎用 /api JSON
    get<T = any>(p: string)  { return request<T>(p, { method: "GET" }); },
    post<T = any>(p: string, body?: any) {
        const isForm = body instanceof FormData;
        return request<T>(p, { method: "POST", body: isForm ? body : JSON.stringify(body ?? {}) });
    },
    put<T = any>(p: string, body?: any) {
        const isForm = body instanceof FormData;
        return request<T>(p, { method: "PUT", body: isForm ? body : JSON.stringify(body ?? {}) });
    },
    patch<T = any>(p: string, body?: any) {
        const isForm = body instanceof FormData;
        return request<T>(p, { method: "PATCH", body: isForm ? body : JSON.stringify(body ?? {}) });
    },
    del<T = any>(p: string)   { return request<T>(p, { method: "DELETE" }); },

    // 認証（web直）
    async register(payload: any) { return postJsonRelative("/register", payload); },
    async login(payload: any)    { return postJsonRelative("/login", payload); },
    async logout()               { return postJsonRelative("/logout", {}); },

    // Billing（/api 側）
    async checkout(payload: { price_id?: string }) { return api.post<{ url: string }>("/billing/checkout", payload); },
    async thanks()               { return api.get("/billing/thanks"); },

    // 認証済みユーザー情報
    async me<T = any>()          { return api.get<T>("/user"); },
};

export default api;
