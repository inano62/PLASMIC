// src/lib/api.ts ーー 決定版（一本化）ーー
export type ApiPath = `/api/${string}`; // /api/* 専用
export type WebPath = `/${string}`;     // /login, /register など（/api/は禁止）

const COMMON: RequestInit = { credentials: "include" };

let TOKEN: string | null = null;
export function setToken(t: string | null) {
    TOKEN = t;
}
async function ensureCsrf() {
    // Sanctum: これが 204 になれば以後 Cookie 認証OK
    await fetch("/sanctum/csrf-cookie", COMMON);
}

async function jsonOrText(res: Response) {
    const ct = res.headers.get("content-type") || "";
    return ct.includes("application/json") ? res.json() : res.text();
}

function boom(status: number, data: unknown): never {
    const message =
        typeof data === "string" ? data :
            (data && typeof data === "object" && "message" in data ? (data as any).message : "Request failed");
    const err = new Error(String(message)) as Error & { status: number; data?: unknown };
    err.status = status; err.data = data;
    throw err;
}

/** ================= Web ルート（/login, /register など） ================= */
export async function postWeb<T = unknown>(path: WebPath, body?: unknown): Promise<T> {
    if (path.startsWith("/api/")) throw new Error("postWeb に /api/* を渡さないでください");
    await ensureCsrf();
    const res = await fetch(path, {
        ...COMMON,
        method: "POST",
        headers: { Accept: "application/json", "Content-Type": "application/json" },
        body: JSON.stringify(body ?? {}),
    });
    if (res.status === 204 || res.status === 205) return undefined as T;
    const data = await jsonOrText(res);
    if (!res.ok) boom(res.status, data);
    return data as T;
}

/** ================= API ルート（/api/*） ================= */
export const api = {
    async get<T = unknown>(path: ApiPath): Promise<T> {
        const res = await fetch(path, {
            credentials: "include",
            method: "GET",
            headers: {
                Accept: "application/json",
                ...(TOKEN ? { Authorization: `Bearer ${TOKEN}` } : {}),
            },
        });
        const data = await jsonOrText(res);
        if (!res.ok) boom(res.status, data);
        return data as T;
    },

    async post<T = unknown>(path: ApiPath, body?: unknown): Promise<T> {
        if (!path.startsWith("/api/")) throw new Error("api.post は /api/* のみ");
        await ensureCsrf();
        const isForm = body instanceof FormData;
        const headers: Record<string, string> = {
            Accept: "application/json",
            ...(isForm ? {} : { "Content-Type": "application/json" }),
            ...(TOKEN ? { Authorization: `Bearer ${TOKEN}` } : {}),
        };
        const res = await fetch(path, {
            credentials: "include",
            method: "POST",
            headers,
            body: isForm ? (body as FormData) : JSON.stringify(body ?? {}),
        });
        const data = await jsonOrText(res);
        if (!res.ok) boom(res.status, data);
        return data as T;
    },

    // ---- よく使うアプリ固有のヘルパ ----
    checkout(payload: { price_id?: string }) {
        return api.post<{ url: string }>("/api/billing/checkout", payload);
    },
    me<T = { id:number; name:string; email:string }>() {
        return api.get<T>("/api/user");
    },
};

export default api;

/** ================= 追加の便利関数（任意） ================= */
// ログイン・登録を web ルートで一括提供しておくと楽
export const auth = {
    login(email: string, password: string) {
        return postWeb<void>("/login", { email, password });
    },
    register(payload: { name: string; email: string; password: string; password_confirmation: string }) {
        return postWeb<void>("/register", payload);
    },
    logout() {
        return postWeb<void>("/logout", {});
    },
};
