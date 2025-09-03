// src/components/UpcomingList.tsx
import { useEffect, useState } from "react";
import { groupByDate } from "../lib/date";

export default function UpcomingList(){
    const [rows, setRows] = useState<any[]|null>(null);
    useEffect(()=>{ fetch('/api/appointments/upcoming?lawyer_id=1&days=14')
        .then(r=>r.json()).then(setRows).catch(()=>setRows([])); },[]);

    if(rows===null) return <p>読み込み中…</p>;
    if(rows.length===0) return <p>今後2週間の予約はありません。</p>;

    const now = Date.now();
    return (
        <div style={{display:"grid",gap:12}}>
            {groupByDate(rows).map(([dateISO, dayRows])=>(
                <div key={dateISO} style={{border:"1px solid #eee",borderRadius:12,padding:12}}>
                    <b>{new Date(dateISO).toLocaleDateString("ja-JP",{weekday:"short",month:"numeric",day:"numeric"})}</b>
                    <ul style={{marginTop:8}}>
                        {dayRows.map((r:any)=> {
                            const t = new Date(r.starts_at);
                            const withinWindow = Math.abs(t.getTime()-now) < 60*60*1000; // ±60分
                            return (
                                <li key={r.id} style={{marginBottom:6}}>
                                    {t.toLocaleTimeString("ja-JP",{hour:"2-digit",minute:"2-digit"})} / {r.client_name}
                                    {withinWindow
                                        ? <a href={`/host?aid=${r.id}`} style={{marginLeft:8, padding:"2px 8px", background:"#ece9ff", borderRadius:8, textDecoration:"none"}}>開始</a>
                                        : <a href={`/host?aid=${r.id}`} style={{marginLeft:8}}>詳細</a>}
                                </li>
                            );
                        })}
                    </ul>
                </div>
            ))}
        </div>
    );
}
