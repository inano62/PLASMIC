// src/pages/Join.tsx
import { useEffect, useRef, useState } from "react";
import { useParams } from "react-router-dom";
import { Room, createLocalTracks, RoomEvent, Track } from "livekit-client";

export default function Join() {
    const { role, code } = useParams();
    const [status, setStatus] = useState("ready");
    
    const localVideo = useRef<HTMLVideoElement>(null);
    const remoteVideo = useRef<HTMLVideoElement>(null);

    useEffect(() => {
        let cleanup: (()=>void)|undefined;

        (async () => {
            try {
                setStatus("exchanging token...");
                const r = await fetch("/api/exchange-token", {
                    method:"POST", headers:{ "Content-Type":"application/json" },
                    body: JSON.stringify({ role, code }),
                });
                if (!r.ok) throw new Error("token exchange failed");
                const { token, livekit_url } = await r.json();

                setStatus("connecting...");
                const room = new Room();
                await room.connect(livekit_url, token);

                room.on(RoomEvent.TrackSubscribed, (pub:any, track:any) => {
                    const t = track ?? pub?.track;
                    if (t?.kind === Track.Kind.Video && remoteVideo.current) t.attach(remoteVideo.current);
                });

                const tracks = await createLocalTracks({ audio:true, video:{ width:1280, height:720 }});
                for (const t of tracks) {
                    await room.localParticipant.publishTrack(t);
                    if (t.kind === Track.Kind.Video && localVideo.current) t.attach(localVideo.current);
                }

                cleanup = () => { tracks.forEach(t=>t.stop()); room.disconnect(); };
                setStatus("connected");
            } catch (e:any) {
                setStatus("error: "+(e?.message ?? e));
            }
        })();

        return () => cleanup?.();
    }, [role, code]);

    return (
        <div className="p-6 space-y-4">
            <p>Status: {status}</p>
            <div className="grid grid-cols-2 gap-4">
                <video ref={localVideo} autoPlay playsInline muted className="w-full bg-black rounded-lg" />
                <video ref={remoteVideo} autoPlay playsInline className="w-full bg-black rounded-lg" />
            </div>
        </div>
    );
}

// export default function Join() {
//     const { role, code } = useParams<{ role: "host" | "guest"; code: string }>();
//     const [status, setStatus] = useState("ready");
//     const localVideo = useRef<HTMLVideoElement>(null);
//     const remoteVideo = useRef<HTMLVideoElement>(null);
//
//     useEffect(() => {
//         if (!role || !code) {
//             setStatus("error: missing role or code");
//             return;
//         }
//
//         let cleanup: (() => void) | undefined;
//
//         (async () => {
//             try {
//                 setStatus("requesting token...");
//                 const r = await fetch("/api/exchange-token", {
//                     method: "POST",
//                     headers: { "Content-Type": "application/json" },
//                     body: JSON.stringify({ role, code }),
//                 });
//                 if (!r.ok) {
//                     const err = await r.json().catch(() => ({}));
//                     throw new Error(err?.error ?? `exchange-token failed (${r.status})`);
//                 }
//                 const { token, livekit_url } = await r.json();
//
//                 setStatus("connecting room...");
//                 const room = new Room();
//                 await room.connect(livekit_url, token);
//
//                 // 新しく購読されたリモート映像
//                 room.on(RoomEvent.TrackSubscribed, (pub: any, track: any) => {
//                     const t = track ?? pub?.track;
//                     if (t?.kind === Track.Kind.Video && remoteVideo.current) {
//                         t.attach(remoteVideo.current);
//                     }
//                 });
//
//                 // すでに参加済みの映像も貼る
//                 const pmap: Map<string, any> =
//                     (room as any).participants ?? (room as any).remoteParticipants ?? new Map();
//                 for (const [, p] of pmap) {
//                     const tracks: Map<string, any> = (p as any).tracks ?? new Map();
//                     for (const [, pub] of tracks) {
//                         const t = (pub as any).track;
//                         if (t?.kind === Track.Kind.Video && remoteVideo.current) {
//                             t.attach(remoteVideo.current);
//                         }
//                     }
//                 }
//
//                 // 自分のトラックを作成・Publish・ローカル表示
//                 const locals = await createLocalTracks({
//                     audio: { echoCancellation: true, noiseSuppression: true },
//                     video: { width: 1280, height: 720 },
//                 });
//                 for (const t of locals) {
//                     await room.localParticipant.publishTrack(t);
//                     if (t.kind === Track.Kind.Video && localVideo.current) {
//                         t.attach(localVideo.current);
//                     }
//                 }
//
//                 cleanup = () => {
//                     locals.forEach((t) => t.stop());
//                     room.disconnect();
//                 };
//
//                 setStatus("connected");
//             } catch (e: any) {
//                 setStatus("error: " + (e?.message ?? String(e)));
//             }
//         })();
//
//         return () => cleanup?.();
//     }, [role, code]);
//
//     return (
//         <div style={{ padding: 12 }}>
//             <p>Status: {status}</p>
//             <div className="max-w-3xl mx-auto px-4 py-16">
//                 <h1 className="text-3xl font-bold">入室</h1>
//                 <p className="mt-2 text-slate-600">このページは既存のJoin.tsxを使用してください。</p>
//             </div>
//             <div  style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 12 }}>
//                 <video ref={localVideo} autoPlay playsInline muted style={{ width: "100%", background: "#000" }} />
//                 <video ref={remoteVideo} autoPlay playsInline style={{ width: "100%", background: "#000" }} />
//             </div>
//         </div>
//     );
// }
