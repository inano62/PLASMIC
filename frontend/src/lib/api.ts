// src/lib/api.ts
export const API = {
    get:  (p: string, init?: RequestInit) => fetch(`/api${p}`, { ...init }),
    post: (p: string, body: any, init?: RequestInit) =>
        fetch(`/api${p}`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(body), ...init }),
};