// src/lib/api.ts
let TOKEN: string | null = null;

// .env から優先、なければ 8000/api
export const API_BASE =
    import.meta.env.VITE_API_BASE ?? "http://localhost:8000/api";

export function setToken(token: string | null) {
    TOKEN = token;
}

function buildUrl(path: string) {
    const p = path.startsWith("/") ? path.slice(1) : path; // 先頭の / を除去
    return `${API_BASE}/${p}`;
}

async function request<T>(path: string, init: RequestInit) {
    const headers: Record<string, string> = {
        Accept: "application/json",
        ...(init.headers as Record<string, string> | undefined),
    };

    // FormData 以外なら Content-Type を自動付与
    const isForm = init.body instanceof FormData;
    if (!isForm && init.body !== undefined) {
        headers["Content-Type"] = headers["Content-Type"] ?? "application/json";
    }
    if (TOKEN) headers["Authorization"] = `Bearer ${TOKEN}`;

    const res = await fetch(buildUrl(path), { ...init, headers });
    let data: any = null;
    try {
        data = await res.json();
    } catch {
        /* JSON でないレスポンスは無視 */
    }
    if (!res.ok) {
        throw new Error(data?.message || data?.error || res.statusText);
    }
    return data as T;
}

// ---- 公開API ----
async function get<T = any>(path: string) {
    return request<T>(path, { method: "GET" });
}
async function post<T = any>(path: string, body?: any) {
    const isForm = body instanceof FormData;
    return request<T>(path, {
        method: "POST",
        body: isForm ? body : JSON.stringify(body ?? {}),
    });
}

async function put<T = any>(path: string, body?: any) {
    const isForm = body instanceof FormData;
    return request<T>(path, {
        method: "PUT",
        body: isForm ? body : JSON.stringify(body ?? {}),
    });
}
async function del<T = any>(path: string) {
    return request<T>(path, { method: "DELETE" });
}
async function upload<T = any>(path: string, form: FormData) {
    // Content-Type は FormData に任せる
    return request<T>(path, { method: "POST", body: form });
}

// 既存コード向けエイリアス（互換）
export const jget = get;
export const jpost = post;
export const jput = put;
export const jdel = del;
export const jupload = upload;

// まとめ export
// src/lib/api.ts（末尾あたり）
export const API = {
    base: API_BASE,
    get, post, put, del, upload, setToken,
    // 互換：昔の呼び方を残す
    jget: get, jpost: post, jput: put, jdel: del, jupload: upload,
};
export default API;
