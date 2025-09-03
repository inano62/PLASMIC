// src/components/RemoteFocus.tsx
import { useTracks, TrackLoop, VideoRenderer } from "@livekit/components-react";
import { Track } from "livekit-client";

export default function RemoteFocus() {
    // カメラ映像だけを TrackRef として取得（プレースホルダも含む）
    const tracks = useTracks(
        [{ source: Track.Source.Camera, withPlaceholder: true }],
        { onlySubscribed: true } // 任意
    );

    // 自分以外
    const remote = tracks.filter(t => !t.participant.isLocal);

    return (
        <div style={{ width: "100%", height: "100%" }}>
            {/* TrackRef の配列をコンテキストに流し込む */}
            <TrackLoop tracks={remote}>
                {/* ここでは TrackRef を context から受けるので OK */}
                <VideoRenderer className="w-full h-full" />
            </TrackLoop>
        </div>
    );
}
