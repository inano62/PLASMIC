// import { useEffect } from "react";
// import { useRoomContext } from "@livekit/components-react";

import { useRoomContext } from "@livekit/components-react";
import { RoomEvent } from "livekit-client";
import { useEffect } from "react";

export default function AutoPinRemote() {
    const room = useRoomContext();
    useEffect(() => {
        if (!room) return;
        const onJoin = (p:any) => { if (!p.isLocal) room.localParticipant.pinParticipant(p); };
        room.on(RoomEvent.ParticipantConnected, onJoin);
        // 既にいる相手へも
        room.participants.forEach((p) => { if (!p.isLocal) room.localParticipant.pinParticipant(p); });
        return () => room.off(RoomEvent.ParticipantConnected, onJoin);
    }, [room]);
    return null;
}
