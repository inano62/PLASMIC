// src/pages/DevCall.tsx
import { useRef, useState } from "react";
import { Room, RoomEvent, Track, createLocalTracks } from "livekit-client";
export default function DevCall(){
    const [status,setStatus]=useState("ready");
    const localRef=useRef<HTMLVideoElement>(null);
    const remoteRef=useRef<HTMLVideoElement>(null);
    async function join(){
        setStatus("joining...");
        const roomName = "dev-room";
        const { token, url } = await fetch("/api/dev/token",{method:"POST",headers:{'Content-Type':'application/json'},body:JSON.stringify({room:roomName,identity:"dev_"+crypto.randomUUID()})}).then(r=>r.json());
        const room = new Room(); await room.connect(url, token, { publishDefaults:{ simulcast:false }});
        room.on(RoomEvent.TrackSubscribed, (_p, t)=>{ if(t?.kind===Track.Kind.Video && remoteRef.current) t.attach(remoteRef.current); });
        const tracks = await createLocalTracks({ audio:true, video:{width:1280,height:720} });
        for(const t of tracks){ await room.localParticipant.publishTrack(t); if(t.kind==='video' && localRef.current) t.attach(localRef.current); }
        setStatus("connected");
    }
    return <div style={{padding:12}}>
        <p>Status: {status}</p>
        <button onClick={join}>入室（dev-room）</button>
        <div style={{display:"grid",gridTemplateColumns:"1fr 1fr",gap:12,marginTop:12}}>
            <video ref={localRef} autoPlay playsInline muted style={{background:"#000",width:"100%"}}/>
            <video ref={remoteRef} autoPlay playsInline style={{background:"#000",width:"100%"}}/>
        </div>
    </div>;
}
