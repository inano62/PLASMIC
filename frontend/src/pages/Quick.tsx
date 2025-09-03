import { useEffect, useRef, useState } from "react";
import {

    Room,
    RoomEvent,
    Track,
    createLocalTracks,
} from "livekit-client";

/** トークン取得。API が {token,url} でも {token,livekit_url} でも拾えるよう吸収 */
async function fetchToken(room: string, identity: string) {
    const r = await fetch("/api/dev/token", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ room, identity }),
    });
    const text = await r.text();
    if (!r.ok) throw new Error(text);
    let obj: any;
    try {
        obj = JSON.parse(text);
    } catch {
        throw new Error(`Token API returned non-JSON: ${text.slice(0, 120)}`);
    }
    return { token: obj.token as string, url: (obj.url ?? obj.livekit_url) as string };
}

export default function Quick() {
    const [status, setStatus] = useState("ready");
    const localVideo = useRef<HTMLVideoElement>(null);
    const remoteVideo = useRef<HTMLVideoElement>(null);

    useEffect(() => {
        let cleanup: (() => void) | undefined;

        (async () => {
            try {
                setStatus("requesting token…");
                const roomName = "demo"; // ←必要なら予約IDなどに置換
                const identity = "user_" + crypto.randomUUID();
                const { token, url } = await fetchToken(roomName, identity);

                setStatus("connecting room…");
                const room: Room = await new Room();
                await room.connect(url, token, {
                    videoCaptureDefaults: { facingMode: "user" },
                    publishDefaults: { simulcast: false },
                });

                // リモート映像がサブスクされたら貼る
                room.on(RoomEvent.TrackSubscribed, (_pub, track) => {
                    if (track?.kind === Track.Kind.Video && remoteVideo.current) {
                        track.attach(remoteVideo.current);
                    }
                });
                // 離脱時は外す（見栄え用）
                room.on(RoomEvent.TrackUnsubscribed, (_pub, track) => {
                    if (track?.kind === Track.Kind.Video && remoteVideo.current) {
                        track.detach(remoteVideo.current);
                    }
                });

                // 既に入っている参加者のトラックも反映（v1/v2両対応）
                const participants =
                    // @ts-ignore
                    (room.participants as Map<string, any>) ??
                    // @ts-ignore
                    (room.remoteParticipants as Map<string, any>) ??
                    new Map();
                for (const [, p] of participants) {
                    const tracks: Map<string, any> = p.tracks ?? new Map();
                    for (const [, pub] of tracks) {
                        const t = pub.track;
                        if (t?.kind === Track.Kind.Video && remoteVideo.current) {
                            t.attach(remoteVideo.current);
                        }
                    }
                }

                // 自分のカメラ/マイクを作成→publish→ローカルに貼る
                const localTracks = await createLocalTracks({
                    audio: { echoCancellation: true, noiseSuppression: true },
                    video: { width: 1280, height: 720 },
                });
                for (const t of localTracks) {
                    await room.localParticipant.publishTrack(t);
                    if (t.kind === Track.Kind.Video && localVideo.current) {
                        t.attach(localVideo.current);
                    }
                }

                cleanup = () => {
                    localTracks.forEach((t) => t.stop());
                    room.disconnect();
                };

                setStatus("connected");
            } catch (e: any) {
                setStatus("error: " + String(e?.message ?? e));
            }
        })();

        return () => cleanup?.();
    }, []);

    return (
        <div style={{ padding: 12 }}>
            <p>Status: {status}</p>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
                <video ref={localVideo} autoPlay playsInline muted style={{ width: "100%", background: "#000" }} />
                <video ref={remoteVideo} autoPlay playsInline style={{ width: "100%", background: "#000" }} />
            </div>
        </div>
    );
}
