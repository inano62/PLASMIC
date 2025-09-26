// src/pages/Host.tsx
import { useEffect, useMemo, useState } from "react";
import { LiveKitRoom, VideoConference } from "@livekit/components-react";
import type { Room, DisconnectReason } from "livekit-client";
import { RoomEvent, type LocalAudioTrack } from "livekit-client";
import "@livekit/components-styles";

import { emitCallEvent } from "../lib/callEvents";
import { api as API } from "../lib/api";

type S = { room?: string; token?: string; url?: string; err?: string };

export default function Host() {
    const [state, setState] = useState<S>({});
    const roomName = state.room ?? "";                 // ← state定義の後で派生させる

    // ルーム名の決定（URL ?room=... が無ければ生成してURLに埋め込む）
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

    // roomが確定したら token を取得
    useEffect(() => {
        if (!state.room) return;
        (async () => {
            try {
                const identity = "host-" + crypto.randomUUID().slice(0, 8);
                const res = await API.post<{ token: string }>("/dev/token", {
                    room: state.room,
                    identity,
                    name: "host",
                });
                setState((s) => ({ ...s, token: res.token }));
            } catch (e: any) {
                setState((s) => ({ ...s, err: e.message ?? String(e) }));
            }
        })();
    }, [state.room]);

    // room確定直後に“保険の start”を一度だけ送る
    useEffect(() => {
        if (!roomName) return;
        emitCallEvent(roomName, "start");
    }, [roomName]);

    // 30秒ごとの heartbeat
    useEffect(() => {
        if (!roomName) return;
        const id = setInterval(() => emitCallEvent(roomName, "heartbeat"), 30_000);
        return () => clearInterval(id);
    }, [roomName]);

    // タブ閉じ/離脱の保険
    useEffect(() => {
        if (!roomName) return;
        const handler = () => emitCallEvent(roomName, "end");
        window.addEventListener("pagehide", handler);
        window.addEventListener("beforeunload", handler);
        return () => {
            window.removeEventListener("pagehide", handler);
            window.removeEventListener("beforeunload", handler);
        };
    }, [roomName]);

    if (state.err) return <div>エラー: {state.err}</div>;
    if (!state.token || !state.room) return <div>入室準備中…</div>;

    const envUrl = import.meta.env.VITE_LIVEKIT_URL as string | undefined;
    const localDefault = location.hostname === "localhost" ? "ws://localhost:7880" : undefined;
    const serverUrl = state.url ?? envUrl ?? localDefault ?? "ws://localhost:7880";

    const guestUrl = (() => {
        const u = new URL(location.origin + "/wait");
        if (state.room) u.searchParams.set("room", state.room);
        return u.toString();
    })();

    if (state.err) return <div>エラー: {state.err}</div>;
    if (!state.token || !state.room) return <div>入室準備中…</div>;

    return (
        <div style={{ height: "100vh" }}>
            <div style={{ position: "absolute", zIndex: 10, top: 8, left: 8, background: "white", padding: 8, borderRadius: 8 }}>
                <div style={{ fontSize: 12, marginBottom: 4 }}>ゲスト参加URL</div>
                <div style={{ display: "flex", gap: 8 }}>
                    <input value={guestUrl} readOnly style={{ width: 320 }} />
                    <button onClick={() => navigator.clipboard.writeText(guestUrl)}>コピー</button>
                </div>
            </div>

            <LiveKitRoom
                token={state.token}
                serverUrl={serverUrl}
                connect
                audio
                video
                // 接続成功 → start（冪等）
                onConnected={(room: Room) => {
                    if (!room?.name) return;
                    emitCallEvent(room.name, "start");

                    // 無音検知（任意）
                    room.on(RoomEvent.LocalTrackPublished, (pub) => {
                        const track = pub.track as LocalAudioTrack | undefined;
                        if (track?.mediaStreamTrack) {
                            const ctx = new AudioContext();
                            const source = ctx.createMediaStreamSource(new MediaStream([track.mediaStreamTrack]));
                            const analyser = ctx.createAnalyser();
                            source.connect(analyser);
                            const data = new Uint8Array(analyser.fftSize);
                            let quietFrames = 0;
                            const tick = () => {
                                analyser.getByteTimeDomainData(data);
                                const variance = data.reduce((s, v) => s + Math.abs(v - 128), 0) / data.length;
                                quietFrames = variance < 1.5 ? quietFrames + 1 : 0;
                                if (quietFrames === 60) {
                                    emitCallEvent(room.name, "silence", { message: "silence detected on local audio track" });
                                }
                                if (room.state === "connected") requestAnimationFrame(tick);
                            };
                            tick();
                        }
                    });
                }}
                // 切断 → end
                onDisconnected={(room: Room, _reason?: DisconnectReason) => {
                    if (!room?.name) return;
                    emitCallEvent(room.name, "end");
                }}
                // エラー
                onError={(e) => {
                    if (!roomName) return;
                    emitCallEvent(roomName, "error", { message: String(e) });
                }}
                style={{ height: "100%" }}
            >
                <VideoConference />
            </LiveKitRoom>

        </div>
    );
}
