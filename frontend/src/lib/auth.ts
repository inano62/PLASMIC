export const ADMIN_TOKEN_KEY = "admin.token";
export function setToken(t: string) { localStorage.setItem('token', t); }
export function getToken(): string | null { return localStorage.getItem('token'); }
export function clearToken() { localStorage.removeItem('token'); }

export async function api<T=any>(path: string, init: RequestInit = {}): Promise<T> {
    const token = getToken();
    const headers = new Headers(init.headers || {});
    headers.set('Content-Type','application/json');
    if (token) headers.set('Authorization', `Bearer ${token}`);
    const res = await fetch(`/api${path}`, { ...init, headers });
    if (res.status === 401) throw new Error('UNAUTH');
    if (!res.ok) throw new Error(await res.text());
    return res.status === 204 ? (undefined as any) : res.json();
}

export async function login(email: string, password: string) {
    const r = await fetch('/api/login', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ email, password }),
    });
    if (!r.ok) throw new Error('LOGIN_FAILED');
    const { token, user } = await r.json();
    setToken(token);
    return user;
}

export async function me() { return api('/me'); }
export async function logout() { await api('/logout', { method:'POST' }); clearToken(); }
