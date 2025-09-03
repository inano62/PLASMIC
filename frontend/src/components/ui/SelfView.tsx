import { useLocalParticipant, useTracks, VideoTrack } from "@livekit/components-react";
import { Track } from "livekit-client";

export default function SelfView() {
    const { localParticipant } = useLocalParticipant();

    // まだ参加者が初期化されてない間のガード
    if (!localParticipant) return null;

    // 自分のカメラだけを 1 本取得（placeholder で黒画面フォールバック）
    const tracks = useTracks([
        { participant: localParticipant, source: Track.Source.Camera, withPlaceholder: true },
    ]);

    // 見つからなければ何も描かない（ここで undefined を踏まない）
    const cam = tracks[0];
    if (!cam) return null;

    return (
        <div
            style={{
                position: "absolute",
                right: 16,
                bottom: 16,
                width: 220,
                aspectRatio: "16 / 9",
                borderRadius: 12,
                overflow: "hidden",
                boxShadow: "0 8px 24px rgba(0,0,0,.35)",
                zIndex: 10,
            }}
        >
            <VideoTrack trackRef={cam} />
        </div>
    );
}
