// src/lib/api.ts
const BASE = "/api";

// ---- raw Response を返す版（Wait.tsx などが利用）
async function get(path: string, init?: RequestInit): Promise<Response> {
    return fetch(BASE + path, {
        credentials: "include",
        headers: { Accept: "application/json", ...(init?.headers || {}) },
        ...init,
    });
}

async function post(path: string, body?: any, init?: RequestInit): Promise<Response> {
    return fetch(BASE + path, {
        method: "POST",
        credentials: "include",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            ...(init?.headers || {}),
        },
        body: body !== undefined ? JSON.stringify(body) : undefined,
        ...init,
    });
}

// ---- JSON を直接返す便利版（BookingForm.tsx などが利用）
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

// 好みで使えるよう default でもまとめて提供
export default {
    get,
    post,
    getJson: jget,
    postJson: jpost,
};
