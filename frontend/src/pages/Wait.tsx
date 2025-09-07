// src/pages/Wait.tsx
import { useEffect, useState } from "react";
import { LiveKitRoom, VideoConference } from "@livekit/components-react";
import "@livekit/components-styles";
import API from "../lib/api";

type S = { room?: string; token?: string; url?: string; err?: string };

export default function Wait() {
    const [state, setState] = useState<S>({});

    useEffect(() => {
        (async () => {
            try {
                const qs = new URLSearchParams(location.search);
                let room = qs.get("room") || undefined;

                if (!room) {
                    const ticket = qs.get("ticket");
                    if (!ticket) throw new Error("room または ticket が必要です");
                    // ここは /api/wait/resolve に到達する
                    const v = await API.getJson<{ room: string }>(`/wait/resolve?ticket=${encodeURIComponent(ticket)}`);
                    room = v.room;
                }

                const identity = "guest-" + crypto.randomUUID();

                // ✅ JSON を直接返す postJson を使う
                const { token, url } = await API.postJson<{ token: string; url?: string }>(
                    "/dev/token",
                    { room, identity }
                );

                setState({ room, token, url });
            } catch (e: any) {
                setState({ err: e.message || String(e) });
            }
        })();
    }, []);

    if (state.err) return <div>エラー: {state.err}</div>;
    if (!state.token) return <div>入室準備中…</div>;

    const envUrl = import.meta.env.VITE_LIVEKIT_URL as string | undefined;
    const localDefault = location.hostname === "localhost" ? "ws://localhost:7880" : undefined;
    const serverUrl = state.url ?? envUrl ?? localDefault ?? "ws://localhost:7880";

    return (
        <div style={{ height: "100vh" }}>
            <LiveKitRoom serverUrl={serverUrl} token={state.token} connect audio video style={{ height: "100%" }}>
                <VideoConference />
            </LiveKitRoom>
        </div>
    );
}
