// src/lib/api.ts
let TOKEN: string | null = null;

// Vite プロキシに任せる
export const API_BASE = '/api';

export function setToken(token: string | null) { TOKEN = token; }

function buildUrl(path: string) {
    const p = path.startsWith('/') ? path.slice(1) : path;
    return `${API_BASE}/${p}`;
}

// 共通設定（Sanctum対応）
const COMMON: RequestInit = { credentials: 'include' };

async function ensureCsrf() {
    await fetch('/sanctum/csrf-cookie', COMMON);
}

async function request<T>(path: string, init: RequestInit) {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        ...(init.headers as Record<string, string> | undefined),
    };
    const isForm = init.body instanceof FormData;
    if (!isForm && init.body !== undefined) {
        headers['Content-Type'] = headers['Content-Type'] ?? 'application/json';
    }
    if (TOKEN) headers['Authorization'] = `Bearer ${TOKEN}`;

    await ensureCsrf();

    const res = await fetch(buildUrl(path), { ...COMMON, ...init, headers });
    let data: any = null;
    try { data = await res.json(); } catch {}
    if (!res.ok) throw new Error(data?.message || data?.error || res.statusText);
    return data as T;
}

// ---- ここから「末尾付近」 ----
// 公開API関数
export function get<T=any>(path: string) {
    return request<T>(path, { method: 'GET' });
}
export function post<T=any>(path: string, body?: any) {
    const isForm = body instanceof FormData;
    return request<T>(path, { method: 'POST', body: isForm ? body : JSON.stringify(body ?? {}) });
}
export function put<T=any>(path: string, body?: any) {
    const isForm = body instanceof FormData;
    return request<T>(path, { method: 'PUT', body: isForm ? body : JSON.stringify(body ?? {}) });
}
export function del<T=any>(path: string) {
    return request<T>(path, { method: 'DELETE' });
}
export function upload<T=any>(path: string, form: FormData) {
    return request<T>(path, { method: 'POST', body: form });
}

// エイリアス
export const jget = get;
export const jpost = post;
export const jput = put;
export const jdel = del;
export const jupload = upload;

// default export を残すならここ
const API = { base: API_BASE, get, post, put, del, upload, setToken,
    jget, jpost, jput, jdel, jupload };
export default API;
