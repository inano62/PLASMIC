import { useEffect } from "react";
import { useRoomContext } from "@livekit/components-react";
import { RoomEvent } from "livekit-client";

export default function DebugEvents() {
    const room = useRoomContext();
    useEffect(() => {
        if (!room) return;
        const onJoin  = (p:any) => console.log("[remote] joined:", p.identity, "room:", room.name);
        const onLeave = (p:any) => console.log("[remote] left:",   p.identity, "room:", room.name);
        room.on(RoomEvent.ParticipantConnected, onJoin);
        room.on(RoomEvent.ParticipantDisconnected, onLeave);
        return () => {
            room.off(RoomEvent.ParticipantConnected, onJoin);
            room.off(RoomEvent.ParticipantDisconnected, onLeave);
        };
    }, [room]);
    return null;
}
