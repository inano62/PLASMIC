// src/lib/api.ts
import axios from "axios";

export const ADMIN_TOKEN_KEY = "admin_token";

// ========== Token ==========
export function setToken(token: string | null) {
    if (token) localStorage.setItem(ADMIN_TOKEN_KEY, token);
    else localStorage.removeItem(ADMIN_TOKEN_KEY);
}
function getToken() {
    return localStorage.getItem(ADMIN_TOKEN_KEY);
}

// ========== API (Bearer, /api/* 用) ==========
export const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE ?? "/api",
    withCredentials: false, // Bearer なので cookie 不要
});

// Bearer を常に付与
api.interceptors.request.use((config) => {
    const token = getToken();
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

// 401 の時は任意でトークン破棄
api.interceptors.response.use(
    (res) => res,
    (err) => {
        if (err?.response?.status === 401) {
            localStorage.removeItem(ADMIN_TOKEN_KEY);
        }
        return Promise.reject(err);
    }
);

// ========== Web (Sanctum, /login 等の非 /api 用) ==========
const web = axios.create({
    baseURL: "/",               // ルート直下 (/login 等)
    withCredentials: true,      // CSRF Cookie をやり取りする
    xsrfCookieName: "XSRF-TOKEN",
    xsrfHeaderName: "X-XSRF-TOKEN",
});

// /login, /logout などの Web ルート
export type WebPath = `/${string}`;
export async function postWeb<T = unknown>(path: WebPath, body?: unknown): Promise<T> {
    if (path.startsWith("/api/")) throw new Error("postWeb に /api/* は渡せません");
    // Sanctum: 先に CSRF Cookie を取得
    await web.get("/sanctum/csrf-cookie");
    const { data } = await web.post<T>(path, body ?? {});
    return data;
}

// ========== 認証 API (/api/*, Bearer) ==========
export const AuthApi = {
    async login(email: string, password: string) {
        const { data } = await api.post("/login", { email, password });
        setToken(data?.token ?? null);
        return data; // { token, user }
    },
    async me() {
        const { data } = await api.get("/me");
        return data; // { id, name, email, role, ... }
    },
    async logout() {
        try { await api.post("/logout"); } catch { console.error("logout failed"); }
        setToken(null);
    },
};

// ========== ユーティリティ ==========
export async function jupload(url: string, form: FormData) {
    const { data } = await api.post(url, form);
    return data; // { id, url, path } など
}

// （必要なら）PUT/DELETE の JSON ヘルパ
export const jput  = <T=any>(url: string, body?: any) => api.put<T>(url, body).then(r=>r.data);
export const jdel  = <T=any>(url: string)            => api.delete<T>(url).then(r=>r.data);

/* 参考：もし「トークンも Cookie にしたい」ならこんな感じ（今回は不要）
function getCookieToken() {
  const m = document.cookie.match(/(?:^|;\s*)admin_token=([^;]+)/);
  return m ? decodeURIComponent(m[1]) : null;
}
*/
