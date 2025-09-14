// src/lib/api.ts
let TOKEN: string | null = null;

// --- Base URL を env 優先で決定 --------------------------------------
// 例）VITE_API_BASE=http://localhost:8000/api  -> API_BASE='http://localhost:8000/api'
//     未設定                                   -> API_BASE='/api'
const RAW_BASE = (import.meta.env.VITE_API_BASE ?? '/api').trim();
export const API_BASE = RAW_BASE.replace(/\/$/, '');         // 末尾スラッシュ除去
const API_ROOT = API_BASE.replace(/\/api$/, '');              // '/api' を落としてルートを得る（'' or 'http://localhost:8000'）

export function setToken(token: string | null) { TOKEN = token; }

function buildUrl(path: string) {
    const p = path.startsWith('/') ? path.slice(1) : path;
    return `${API_BASE}/${p}`;
}

// 共通設定（Sanctum用 Cookie をやり取りする）
const COMMON: RequestInit = { credentials: 'include' };

// Sanctum の CSRF Cookie を API の「ルート」から取得する
async function ensureCsrf() {
    // 例）相対モード: API_ROOT === '' -> '/sanctum/csrf-cookie'
    //     絶対モード: API_ROOT === 'http://localhost:8000' -> 'http://localhost:8000/sanctum/csrf-cookie'
    const url = `${API_ROOT}/sanctum/csrf-cookie`;
    await fetch(url, COMMON);
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

    // 変更があるメソッドの時は毎回 CSRF を確実に取る（GET でも取っても害はない）
    await ensureCsrf();

    const res = await fetch(buildUrl(path), { ...COMMON, ...init, headers });

    // 可能なら JSON を返す。ダメならテキスト
    const ct = res.headers.get('content-type') || '';
    let data: any = null;
    try {
        data = ct.includes('application/json') ? await res.json() : await res.text();
    } catch {}

    if (!res.ok) {
        const msg = typeof data === 'string' ? data : data?.message || data?.error || res.statusText;
        throw new Error(msg);
    }
    return data as T;
}

// ---- 公開 API 関数 ----
export function get<T = any>(path: string) {
    return request<T>(path, { method: 'GET' });
}
export function post<T = any>(path: string, body?: any) {
    const isForm = body instanceof FormData;
    return request<T>(path, { method: 'POST', body: isForm ? body : JSON.stringify(body ?? {}) });
}
export function put<T = any>(path: string, body?: any) {
    const isForm = body instanceof FormData;
    return request<T>(path, { method: 'PUT', body: isForm ? body : JSON.stringify(body ?? {}) });
}
export function patch<T = any>(path: string, body?: any) {
    const isForm = body instanceof FormData;
    return request<T>(path, { method: 'PATCH', body: isForm ? body : JSON.stringify(body ?? {}) });
}
export function del<T = any>(path: string) {
    return request<T>(path, { method: 'DELETE' });
}
export function upload<T = any>(path: string, form: FormData) {
    return request<T>(path, { method: 'POST', body: form });
}

// エイリアス（互換維持）
export const jget = get;
export const jpost = post;
export const jput = put;
export const jpatch = patch;
export const jdel = del;
export const jupload = upload;

// default export
const API = { base: API_BASE, get, post, put, patch, del, upload, setToken,
    jget, jpost, jput, jpatch, jdel, jupload };
export default API;
