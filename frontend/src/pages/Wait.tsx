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
                    const aid = qs.get("aid");
                    let url = "";
                    if (ticket) url = `/wait/resolve?ticket=${encodeURIComponent(ticket)}`;
                    else if (aid) url = `/wait/resolve?aid=${encodeURIComponent(aid)}`;
                    else throw new Error("room または ticket が必要です");

                    const vres = await API.get(url);
                    if (!vres.ok) throw new Error("ticket/aid の解決に失敗しました");
                    const v = await vres.json();
                    room = v.room;
                }

                const identity = "guest-" + crypto.randomUUID();
                const tres = await API.post("/dev/token", { room, identity });
                if (!tres.ok) throw new Error("トークン取得に失敗しました");
                const t = await tres.json();
                setState({ room, token: t.token, url: t.url });

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
