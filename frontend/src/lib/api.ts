// src/lib/api.ts
const BASE = "/api";

// ---- 内部状態（Bearer トークン or Cookie）
let bearerToken: string | null = null;
export function setToken(token: string | null) {
    bearerToken = token;
}

// 共通ヘッダ（JSONを期待）
function withHeaders(init?: RequestInit): RequestInit {
    const headers: Record<string, string> = {
        Accept: "application/json",
        ...(init?.headers as Record<string, string> | undefined),
    };
    if (bearerToken) headers.Authorization = `Bearer ${bearerToken}`;
    return {
        credentials: bearerToken ? "omit" : "include",
        ...init,
        headers,
    };
}

/* ========== raw Response ========== */
export async function get(path: string, init?: RequestInit): Promise<Response> {
    return fetch(BASE + path, withHeaders(init));
}

export async function post(path: string, body?: any, init?: RequestInit): Promise<Response> {
    const headers = {
        "Content-Type": "application/json",
        ...(init?.headers as Record<string, string> | undefined),
    };
    return fetch(BASE + path, withHeaders({ method: "POST", body: body != null ? JSON.stringify(body) : undefined, headers }));
}

/* ========== JSON 便利版 ========== */
export async function jget<T>(path: string, init?: RequestInit): Promise<T> {
    const res = await get(path, init);
    if (!res.ok) throw new Error(await res.text());
    return (await res.json()) as T;
}

export async function jpost<T>(path: string, body?: any, init?: RequestInit): Promise<T> {
    const res = await post(path, body, init);
    if (!res.ok) throw new Error(await res.text());
    return (await res.json()) as T;
}

export async function jput<T>(path: string, body?: any, init?: RequestInit): Promise<T> {
    const headers = {
        "Content-Type": "application/json",
        ...(init?.headers as Record<string, string> | undefined),
    };
    const res = await fetch(BASE + path, withHeaders({ method: "PUT", body: body != null ? JSON.stringify(body) : undefined, headers }));
    if (!res.ok) throw new Error(await res.text());
    return (await res.json()) as T;
}

export async function jdel<T = { ok: boolean }>(path: string, init?: RequestInit): Promise<T> {
    const res = await fetch(BASE + path, withHeaders({ method: "DELETE", ...(init || {}) }));
    if (!res.ok) throw new Error(await res.text());
    // DELETE でボディが無い場合もあるので空なら {ok:true} を返す
    try { return (await res.json()) as T; } catch { return { ok: true } as T; }
}

/* ========== 認証ヘルパ（お好みで） ========== */
export async function spaLogin(email: string, password: string) {
    await fetch("/sanctum/csrf-cookie", { credentials: "include" });
    const res = await fetch("/login", {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ email, password }),
    });
    if (!res.ok) throw new Error(await res.text());
    setToken(null);
}

export async function tokenLogin(email: string, password: string) {
    const { token } = await jpost<{ token: string }>("/auth/token", { email, password });
    setToken(token);
}
export async function jupload<T = any>(path: string, form: FormData, init?: RequestInit): Promise<T> {
    const res = await fetch("/api" + path, withHeaders({
        method: "POST",
        body: form,
        // Content-Type は指定しない（ブラウザが boundary を付ける）
        ...(init || {}),
    }));
    if (!res.ok) throw new Error(await res.text());
    return (await res.json()) as T;
}
/* ========== default export（1回だけ） ========== */
export default {
    get, post, getJson: jget, postJson: jpost, jput, jdel, jupload,
    setToken, spaLogin, tokenLogin,
};
