// components/EveryoneGrid.tsx
import { GridLayout, ParticipantTile, useTracks } from "@livekit/components-react";
import { Track } from "livekit-client";

export default function EveryoneGrid() {
    const tracks = useTracks([{ source: Track.Source.Camera, withPlaceholder: false }]);

    return (
        <GridLayout className="lk-basic-row" tracks={tracks}>
            {/* GridLayout は子が1要素のみ必要。これがテンプレートになる */}
            <ParticipantTile />
        </GridLayout>
    );
}
