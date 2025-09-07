// pages/Host.tsx
import { useEffect, useState, useRef } from "react";
import { LiveKitRoom, VideoConference } from "@livekit/components-react";
import "@livekit/components-styles";
import  API  from "../lib/api";

type S = { token?: string; url?: string; err?: string };
export default function Host() {
    const [state, setState] = useState<S>({});
    const roomRef = useRef<string>("");

    // ルーム名の決定（1回だけ）
    useEffect(() => {
        const qs = new URLSearchParams(location.search);
        const r = qs.get("room");
        if (r) {
            roomRef.current = r;
        } else {
            const g = "room_" + crypto.randomUUID().slice(0, 8);
            roomRef.current = g;
            const u = new URL(location.href);
            u.searchParams.set("room", g);
            history.replaceState({}, "", u.toString());
        }
    }, []);

    // トークン取得（room が決まってから）
    useEffect(() => {
        if (!roomRef.current) return;
        (async () => {
            try {
                const identity = "host-" + crypto.randomUUID();
                const tres = await API.post("/dev/token", { room: roomRef.current, identity });
                if (!tres.ok) throw new Error("トークン取得に失敗しました");
                const t = await tres.json();
                setState({ token: t.token, url: t.url });
            } catch (e: any) {
                setState({ err: e.message || String(e) });
            }
        })();
    }, [roomRef.current]); // eslint-disable-line react-hooks/exhaustive-deps

    if (state.err) return <div>エラー: {state.err}</div>;
    if (!state.token) return <div>入室準備中…</div>;

    const envUrl = import.meta.env.VITE_LIVEKIT_URL as string | undefined;
    const localDefault = location.hostname === "localhost" ? "ws://localhost:7880" : undefined;
    const serverUrl = state.url ?? envUrl ?? localDefault ?? "ws://localhost:7880";

    const guestUrl = (() => {
        const u = new URL(location.origin + "/wait");
        u.searchParams.set("room", roomRef.current);
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
