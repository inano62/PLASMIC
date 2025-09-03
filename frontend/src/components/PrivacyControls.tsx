import { ControlBar } from "@livekit/components-react";
export default function PrivacyControls() {
    return (
        <div style={{ position: "fixed", left: 12, bottom: 12, zIndex: 60 }}>
            <ControlBar controls={{ microphone:true, camera:true, screenShare:true, chat:true, leave:true, layout:false }} />
        </div>
    );
}
