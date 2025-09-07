// src/lib/api.ts
// export const API = {
//     get:  (p: string, init?: RequestInit) => fetch(`/api${p}`, { ...init }),
//     post: (p: string, body: any, init?: RequestInit) =>
//         fetch(`/api${p}`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body), ...init }),
// };
// export const API = import.meta.env.VITE_API_ORIGIN ?? 'http://localhost:8000';

export async function jget<T>(path: string): Promise<T> {
    const r = await fetch(`${API}${path}`);
    if (!r.ok) throw new Error(await r.text());
    return r.json();
}

export async function jpost<T>(path: string, body: any): Promise<T> {
    const r = await fetch(`${API}${path}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(body),
    });
    if (!r.ok) throw new Error(await r.text());
    return r.json();
}

// type Json = any;

// async function get<T = Json>(url: string, init: RequestInit = {}): Promise<T> {
//     const r = await fetch(url, { ...init, headers: { Accept: "application/json", ...(init.headers || {}) } });
//     if (!r.ok) throw new Error(await r.text());
//     return r.json();
// }

// async function post<T = Json>(url: string, body?: unknown, init: RequestInit = {}): Promise<T> {
//     const r = await fetch(url, {
//         method: "POST",
//         ...init,
//         headers: {
//             "Content-Type": "application/json",
//             Accept: "application/json",
//             ...(init.headers || {}),
//         },
//         body: body === undefined ? undefined : JSON.stringify(body),
//     });
//     if (!r.ok) throw new Error(await r.text());
//     return r.json();
// }

const API = { get, post };
// export default API;      // default export
export { API, get, post }; // named export も提供


// src/lib/api.ts
const BASE = "/api";

async function get(path: string, init?: RequestInit) {
    const res = await fetch(`${BASE}${path}`, {
        credentials: "include",
        headers: { Accept: "application/json", ...(init?.headers || {}) },
        ...init,
    });
    return res;
}

async function post(path: string, body?: any, init?: RequestInit) {
    const res = await fetch(`${BASE}${path}`, {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json", Accept: "application/json", ...(init?.headers || {}) },
        body: body !== undefined ? JSON.stringify(body) : undefined,
        ...init,
    });
    return res;
}

// 便利: JSON を直接返す版（型付きで使える）
async function postJson<T>(path: string, body?: any) {
    const res = await post(path, body);
    if (!res.ok) throw new Error(await res.text());
    return (await res.json()) as T;
}
async function getJson<T>(path: string) {
    const res = await get(path);
    if (!res.ok) throw new Error(await res.text());
    return (await res.json()) as T;
}

export default { get, post, getJson, postJson };
