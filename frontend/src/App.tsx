import { useEffect, useRef, useState } from 'react';
import {
    Room, RoomEvent, Track,
    LocalTrackPublication, LocalVideoTrack, createLocalTracks
} from 'livekit-client';
import { connectFromQueryOrEnv } from './livekit';

export default function App() {
    const [room, setRoom] = useState<Room | null>(null);
    const [err, setErr] = useState('');
    const [isMicOn, setIsMicOn] = useState(false);
    const [isCamOn, setIsCamOn] = useState(false);
    const [isScreenOn, setIsScreenOn] = useState(false);

    const localVideoRef = useRef<HTMLVideoElement>(null);
    const remoteVideoRef = useRef<HTMLVideoElement>(null);
    const remoteAudioRef = useRef<HTMLAudioElement>(null);

    // ① 初回だけ接続（依存配列は空）
    useEffect(() => {
        (async () => {
            try {
                const r = await connectFromQueryOrEnv();
                hookRoom(r);
                setRoom(r);
            } catch (e) {
                setErr(String(e));
            }
        })();
        return () => { room?.disconnect(); };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // ② attach/detach は LiveKit の track.attach()/detach() を使う
    const hookRoom = (r: Room) => {
        r.on(RoomEvent.TrackSubscribed, (track) => {
            if (track.kind === Track.Kind.Video && remoteVideoRef.current) {
                track.attach(remoteVideoRef.current);
            }
            if (track.kind === Track.Kind.Audio && remoteAudioRef.current) {
                track.attach(remoteAudioRef.current);
            }
        });

        r.on(RoomEvent.TrackUnsubscribed, (track) => {
            if (track.kind === Track.Kind.Video && remoteVideoRef.current) {
                track.detach(remoteVideoRef.current);
            }
            if (track.kind === Track.Kind.Audio && remoteAudioRef.current) {
                track.detach(remoteAudioRef.current);
            }
        });

        // ローカルカメラのプレビューは publish/unpublish イベントで更新
        r.on(RoomEvent.LocalTrackPublished, (pub: LocalTrackPublication) => {
            if (pub.source === Track.Source.Camera && localVideoRef.current && pub.track) {
                (pub.track as LocalVideoTrack).attach(localVideoRef.current);
                setIsCamOn(true);
            }
        });
        r.on(RoomEvent.LocalTrackUnpublished, (pub: LocalTrackPublication) => {
            if (pub.source === Track.Source.Camera && localVideoRef.current) {
                pub.track?.detach(localVideoRef.current);
                setIsCamOn(false);
            }
        });
    };

    const startPublishing = async () => {
        if (!room) return;
        try {
            // ③ ユーザー操作の直後に呼ぶと自動再生制限を解除できる
            await room.startAudio();

            const [mic, cam] = await createLocalTracks({ audio: true, video: true });
            await room.localParticipant.publishTrack(mic);
            await room.localParticipant.publishTrack(cam);

            setIsMicOn(true);
            // カメラは LocalTrackPublished で自動 attach されます
        } catch (e) {
            setErr(String(e));
        }
    };

    const toggleMic = async () => {
        if (!room) return;
        try {
            const next = !isMicOn;
            await room.localParticipant.setMicrophoneEnabled(next);
            setIsMicOn(next);
        } catch (e) { setErr(String(e)); }
    };

    const toggleCam = async () => {
        if (!room) return;
        try {
            const next = !isCamOn;
            await room.localParticipant.setCameraEnabled(next);  // ← ここがポイント！
            setIsCamOn(next);
        } catch (e) { setErr(String(e)); }
    };

    const toggleScreen = async () => {
        if (!room) return;
        try {
            const next = !isScreenOn;
            await room.localParticipant.setScreenShareEnabled(next);
            setIsScreenOn(next);
        } catch (e) { setErr(String(e)); }
    };

    const leave = async () => {
        await room?.disconnect();
        setRoom(null);
    };

    if (err) return <div style={{ color: 'salmon' }}>Error: {err}</div>;
    if (!room) return <div>Connecting...</div>;

    return (
        <div style={{ padding: 12, color: '#ddd' }}>
            <div style={{ marginBottom: 8 }}>Joined room: {room.name}</div>
            <div style={{ display: 'flex', gap: 8, marginBottom: 12 }}>
                <button onClick={startPublishing}>Start camera & mic</button>
                <button onClick={toggleMic}>{isMicOn ? 'Mute mic' : 'Unmute mic'}</button>
                <button onClick={toggleCam}>{isCamOn ? 'Stop cam' : 'Start cam'}</button>
                <button onClick={toggleScreen}>{isScreenOn ? 'Stop share' : 'Share screen'}</button>
                <button onClick={leave}>Leave</button>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                <div>
                    <div>Local</div>
                    <video ref={localVideoRef} autoPlay playsInline muted style={{ width: '100%', background: '#222' }} />
                </div>
                <div>
                    <div>Remote</div>
                    <video ref={remoteVideoRef} autoPlay playsInline style={{ width: '100%', background: '#222' }} />
                    <audio ref={remoteAudioRef} />
                </div>
            </div>
        </div>
    );
}
