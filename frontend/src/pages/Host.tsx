import { useEffect, useState } from "react";
import { LiveKitRoom, VideoConference } from "@livekit/components-react";
import "@livekit/components-styles";
import API from "../lib/api";

type S = { room?: string; token?: string; url?: string; err?: string };

export default function Host() {
    const [state, setState] = useState<S>({});

    // ルーム名の決定（state に持つ）
    useEffect(() => {
        const qs = new URLSearchParams(location.search);
        const r = qs.get("room");
        const room = r || `room_${crypto.randomUUID().slice(0, 8)}`;

        if (!r) {
            const u = new URL(location.href);
            u.searchParams.set("room", room);
            history.replaceState({}, "", u.toString());
        }
        setState((s) => ({ ...s, room }));
    }, []);

    // トークン取得（room が決まってから）
    useEffect(() => {
        if (!state.room) return;
        (async () => {
            try {
                const identity = "host-" + crypto.randomUUID().slice(0, 8);
                // ★ API.post は JSON を返す
                const res = await API.post<{ token: string }>("dev/token", {
                    room: state.room,
                    identity,
                    name: "host",
                });
                setState((s) => ({ ...s, token: res.token }));
            } catch (e: any) {
                setState((s) => ({ ...s, err: e.message || String(e) }));
            }
        })();
    }, [state.room]);

    if (state.err) return <div>エラー: {state.err}</div>;
    if (!state.token || !state.room) return <div>入室準備中…</div>;

    const envUrl = import.meta.env.VITE_LIVEKIT_URL as string | undefined;
    const localDefault = location.hostname === "localhost" ? "ws://localhost:7880" : undefined;
    const serverUrl = state.url ?? envUrl ?? localDefault ?? "ws://localhost:7880";

    const guestUrl = (() => {
        const u = new URL(location.origin + "/wait");
        u.searchParams.set("room", state.room!);
        return u.toString();
    })();

    return (
        <div style={{ height: "100vh" }}>
            <div style={{ position: "absolute", zIndex: 10, top: 8, left: 8, background: "white", padding: 8, borderRadius: 8 }}>
                <div style={{ fontSize: 12, marginBottom: 4 }}>ゲスト参加URL</div>
                <div style={{ display: "flex", gap: 8 }}>
                    <input value={guestUrl} readOnly style={{ width: 320 }} />
                    <button onClick={() => navigator.clipboard.writeText(guestUrl)}>コピー</button>
                </div>
            </div>

            <LiveKitRoom serverUrl={serverUrl} token={state.token} connect audio video style={{ height: "100%" }}>
                <VideoConference />
            </LiveKitRoom>
        </div>
    );
}
