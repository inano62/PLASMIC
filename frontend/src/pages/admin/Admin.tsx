import { useState, useEffect } from "react";
import ScheduleBoard from "../../components/ScheduleBoard";

export default function Admin(){
    const [tab, setTab] = useState<"today"|"all"|"settings">("all");
    return (
        <div style={{padding:16}}>
            <div style={{display:"flex",gap:10,marginBottom:12}}>
                <button onClick={()=>setTab("all")}>スケジュール</button>
                <button onClick={()=>setTab("today")}>今日の予約</button>
                <button onClick={()=>setTab("settings")}>設定</button>
            </div>

            {tab==="all" && <ScheduleBoard/>}
            {tab==="today" && <TodayBlock/>}
            {tab==="settings" && <SettingsBlock/>}
        </div>
    );
}

function TodayBlock(){
    const [rows,setRows]=useState<any[]|null>(null);
    useEffect(()=>{
        fetch('/api/appointments/nearby?lawyer_id=1')
            .then(r=>r.json()).then(setRows).catch(()=>setRows([]));
    },[]);
    if(rows===null) return <p>読み込み中…</p>;
    if(!rows.length) return <p>本日の予約はありませんでした。</p>;
    return <ul>
        {rows.map(r=>(
            <li key={r.id}>
                {new Date(r.starts_at).toLocaleString()} / {r.client_name}
                <a href={`/host?aid=${r.id}`} style={{marginLeft:8}}>開始</a>
            </li>
        ))}
    </ul>;
}

function SettingsBlock(){ return <div>（既存の設定フォーム）</div>; }
// Admin のスケジュール一覧で使う関数
