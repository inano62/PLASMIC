// src/lib/api.ts
const BASE = import.meta.env.VITE_API_BASE ?? "http://localhost:8000/api";
console.log("[API] BASE =", BASE);

let bearerToken: string | null = null;
export function setToken(token: string | null) { bearerToken = token; }

export async function api(path: string, init: RequestInit = {}) {
    const url = `${BASE}${path.startsWith("/") ? path : `/${path}`}`;
    const headers = { "Content-Type": "application/json", ...(init.headers || {}) };
    const res = await fetch(url, { ...init, headers });
    let data: any = null;
    try { data = await res.json(); } catch {}
    if (!res.ok) throw new Error(data?.message || data?.error || res.statusText);
    return data;
}

export const API = {
    get:  (p: string) => api(p),
    post: (p: string, body: any) => api(p, { method: "POST", body: JSON.stringify(body) }),
};

function withHeaders(init?: RequestInit): RequestInit {
    const headers: Record<string,string> = { Accept: "application/json", ...(init?.headers as any) };
    if (bearerToken) headers.Authorization = `Bearer ${bearerToken}`;
    return { credentials: bearerToken ? "omit" : "include", ...init, headers };
}

async function handle(res: Response) {
    if (!res.ok) {
        const text = await res.text().catch(()=> "");
        console.error("[API] error", res.status, text);
        throw new Error(`HTTP ${res.status} ${text}`);
    }
    if (res.status === 204) return null as any;
    try { return await res.json(); } catch { return {} as any; }
}

/* ===== JSON ===== */
export async function jget<T>(path: string, init?: RequestInit): Promise<T> {
    console.log("[API] GET", BASE + path);
    return handle(await fetch(BASE + path, withHeaders(init)));
}
export async function jpost<T>(path: string, body?: any, init?: RequestInit): Promise<T> {
    console.log("[API] POST", BASE + path, body);
    return handle(await fetch(BASE + path, withHeaders({
        method: "POST", headers: { "Content-Type": "application/json", ...(init?.headers || {}) },
        body: body != null ? JSON.stringify(body) : undefined,
    })));
}
export async function jput<T>(path: string, body?: any, init?: RequestInit): Promise<T> {
    console.log("[API] PUT", BASE + path, body);
    return handle(await fetch(BASE + path, withHeaders({
        method: "PUT", headers: { "Content-Type": "application/json", ...(init?.headers || {}) },
        body: body != null ? JSON.stringify(body) : undefined,
    })));
}
export async function jdel<T = { ok: boolean }>(path: string, init?: RequestInit): Promise<T> {
    console.log("[API] DELETE", BASE + path);
    return handle(await fetch(BASE + path, withHeaders({ method:"DELETE", ...(init||{}) })));
}

/* ===== upload (必ず BASE を使用) ===== */
export async function jupload<T>(path: string, fd: FormData): Promise<T> {
    console.log("[API] UPLOAD", BASE + path);
    return handle(await fetch(BASE + path, withHeaders({ method:"POST", body: fd })));
}

/* 互換 alias と default export（1回だけ） */
const API = { jget, jpost, jput, jdel, jupload, setToken, getJson: jget, postJson: jpost };
export default API;
