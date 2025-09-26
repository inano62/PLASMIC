import { useEffect, useState } from "react";
import { LiveKitRoom, VideoConference } from "@livekit/components-react";
import "@livekit/components-styles";
// ✅ 必要なものだけ import
import {api} from "../lib/api";


type S = { room?: string; token?: string; url?: string; err?: string };
const API = api
export default function Wait() {
    const [state, setState] = useState<S>({});

    useEffect(() => {
        (async () => {
            try {
                const qs = new URLSearchParams(location.search);
                let room = qs.get("room") || undefined;

                // room が無ければ ticket/aid から解決
                if (!room) {
                    const ticket = qs.get("ticket");
                    const aid = qs.get("aid");
                    if (!ticket && !aid) throw new Error("room または ticket が必要です");

                    const url = ticket
                        ? `/wait/resolve?ticket=${encodeURIComponent(ticket)}`
                        : `/wait/resolve?aid=${encodeURIComponent(aid!)}`;

                    // API.get は JSON を返す
                    const v = await API.get<{ room: string }>(url);
                    room = v.room;
                    if (!room) throw new Error("ticket/aid の解決に失敗しました");
                }

                const identity = "guest-" + crypto.randomUUID().slice(0, 8);
                // ★ dev/token に POST（API.post は JSON を返す）
                const res = await API.post<{ token: string }>("dev/token", {
                    room,
                    identity,
                    name: identity,
                });

                setState({ room, token: res.token });
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
