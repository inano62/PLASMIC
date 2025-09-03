import React, { useMemo } from "react";
import { GridLayout, ParticipantTile, useParticipants } from "@livekit/components-react";

export default function RemoteGridOnly() {
    const participants = useParticipants() ?? []; // 念のため
    const tiles = useMemo(
        () =>
            (participants ?? [])
                .filter(p => !p.isLocal)
                .map(p => <ParticipantTile key={p.sid} participant={p} />),
        [participants]
    );

    // children を「必ず配列」で渡す
    return <GridLayout>{(tiles as unknown as React.ReactNode[]) ?? []}</GridLayout>;
}
