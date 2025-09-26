// src/lib/api.ts
import axios from "axios";

export type ApiPath = `/api/${string}`; // /api/*
export type WebPath = `/${string}`;     // /login など（/api/*はNG）
const API_BASE = import.meta.env.VITE_API_BASE || "http://localhost:8000/api";
const BASE = import.meta.env.VITE_API_BASE || "http://localhost:8000";
// let TOKEN: string | null = null;
// export function setToken(token: string | null) { TOKEN = token; }

// ここを“ただの data を返す axios”に統一
export const http = axios.create({
    baseURL: BASE,
    withCredentials: true, // ← Cookie送受信
    headers: {
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
    },
    xsrfCookieName: "XSRF-TOKEN",   // ← 明示
    xsrfHeaderName: "X-XSRF-TOKEN",
});
http.interceptors.response.use((r) => r.data);

// ===== Web ルート（/login, /logout など） =====
export async function postWeb<T = unknown>(path: WebPath, body?: unknown): Promise<T> {
    if (path.startsWith("/api/")) throw new Error("postWeb に /api/* は渡せません");
    // Sanctum: 先にCSRF Cookie
    await http.get("/sanctum/csrf-cookie");
    return http.post<T>(path, body ?? {});
}

// ===== API ルート（/api/*） =====
export const api = axios.create({
    baseURL: API_BASE,         // ← "/api" を含む
    withCredentials: false,    // ← Cookie使わない
    headers: { Accept: "application/json" },
});
api.interceptors.response.use((r) => r.data as any);

// ---- トークン管理（localStorageに保存）----
const TOKEN_KEY = "ADMIN_TOKEN";
export function setToken(t: string | null) {
    if (t) localStorage.setItem(TOKEN_KEY, t);
    else localStorage.removeItem(TOKEN_KEY);
    api.defaults.headers.common.Authorization = t ? `Bearer ${t}` : undefined;
}
// 起動時に復元
setToken(localStorage.getItem(TOKEN_KEY));
// ---- API 呼び出しヘルパ ----
export const AuthApi = {
    login(email: string, password: string) {
        return api.post<{ token: string; user: { id:number; name:string; email:string } }>(
            "/auth/token",
            { email, password }
        );
    },
    me() {
        return api.get<{ user: { id:number; name:string; email:string } | null }>("/whoami");
    },
};