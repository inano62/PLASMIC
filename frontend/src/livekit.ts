import { Room } from "livekit-client";

export async function connectFromQueryOrEnv() {
    const qp = new URLSearchParams(location.search);
    const token = (qp.get("token") ?? import.meta.env.VITE_LK_TOKEN ?? "").trim();
    const url   = (import.meta.env.VITE_LK_URL ?? "").trim();
    if (!url || !token) throw new Error("VITE_LK_URL または VITE_LK_TOKEN が未設定です");

    // 事前に validate（失敗内容が分かりやすい）
    const http = url.startsWith("wss://") ? url.replace("wss://","https://")
        : url.replace("ws://","http://");
    const res = await fetch(`${http}/rtc/validate?access_token=${encodeURIComponent(token)}`);
    const txt = (await res.text()).trim();
    if (!res.ok || txt !== "success") throw new Error(`token validate failed: ${txt}`);

    const room = new Room();
    await room.connect(url, token);
    return room;
}
