// src/pages/admin/Admin.tsx
import { useEffect, useMemo, useState } from "react";
import {TimeRail} from "../components/TimeRail.tsx";
type Appt = { id:number; client_name:string; starts_at:string; room:string };

export default function Admin(){
    const [rows, setRows] = useState<Appt[]>([]);
    useEffect(()=>{
        fetch('/api/appointments/nearby?lawyer_id=1')
            .then(r=>r.json()).then(setRows);
    },[]);

    function canStart(iso: string) {
        const start = new Date(iso).getTime();
        const now   = Date.now();
        const fiveMinBefore = start - 5 * 60 * 1000;
        const sixtyMinAfter = start + 60 * 60 * 1000;
        return now >= fiveMinBefore && now <= sixtyMinAfter;
    }
    // 今週の 9:00〜17:30 / 30分のグリッド
    const grid = useMemo(()=>{
        const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const now = new Date();
        const monday = new Date(now); const d = (now.getDay()+6)%7; monday.setDate(now.getDate()-d);
        const days = [...Array(5)].map((_,i)=> new Date(monday.getFullYear(), monday.getMonth(), monday.getDate()+i));
        const slots = [...Array(17)].map((_,i)=> i); // 9:00〜17:30 (17コマ)
        return {days, slots, tz};
    },[]);

    function match(ap:Appt, day:Date, slotIndex:number){
        const t = new Date(ap.starts_at);
        return t.getFullYear()===day.getFullYear()
            && t.getMonth()===day.getMonth()
            && t.getDate()===day.getDate()
            && t.getHours()===9 + Math.floor(slotIndex/2)
            && t.getMinutes()===(slotIndex%2)*30;
    }

    return (
        <div style={{padding:16}}>
            <h2>今週の予約</h2>
            <div className="card">
                <div className="card-header">今週の予約</div>
                <div className="card-body d-flex">
                    <TimeRail open="09:00" close="17:00" step={30} />
                    {/* 予約ブロックはここに重ね描き（APIが空でもレールは見える） */}
                </div>
            </div>
            <li key={r.id} className="row">
                <div className="time">{new Date(r.starts_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</div>
                <div className="name">{r.client_name}</div>

                {canStart(r.starts_at) ? (
                    <a href={`/host?aid=${r.id}`} className="btn-primary">開始</a>
                ) : (
                    <a href={`/admin/appointments/${r.id}`} className="btn-ghost">詳細</a>
                )}

                {new Date(r.starts_at).getTime() < Date.now() && !canStart(r.starts_at) && (
                    <span className="muted">終了</span>
                )}
            </li>

            <div style={{display:"grid", gridTemplateColumns:"120px repeat(5,1fr)", borderTop:"1px solid #eee"}}>
                <div></div>
                {grid.days.map(d=><div key={d.toDateString()} style={{padding:8, fontWeight:700}}>
                    {d.toLocaleDateString("ja-JP",{weekday:"short", month:"numeric", day:"numeric"})}
                </div>)}
                {grid.slots.map(i=>(
                    <>
                        <div key={`t-${i}`} style={{padding:8, borderTop:"1px solid #f2f2f2"}}>
                            {`${String(9+Math.floor(i/2)).padStart(2,"0")}:${i%2? "30":"00"}`}
                        </div>
                        {grid.days.map((day,di)=>(
                            <div key={`c-${di}-${i}`} style={{padding:6, borderTop:"1px solid #f2f2f2", minHeight:36}}>
                                {rows.filter(a=>match(a,day,i)).map(a=>(
                                    <a key={a.id} href={`/host?aid=${a.id}`} style={{display:"inline-block", padding:"4px 8px", borderRadius:8, background:"#ece9ff", textDecoration:"none"}}>
                                        {a.client_name}
                                    </a>
                                ))}
                            </div>
                        ))}
                    </>
                ))}
            </div>
        </div>
    );
}
