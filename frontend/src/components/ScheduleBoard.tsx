import { useEffect, useMemo, useState } from "react";

type Row = { id:number; client_name:string; starts_at:string; room:string; status:string };

function groupByDate(rows: Row[]) {
    const map: Record<string, Row[]> = {};
    for (const r of rows) {
        const d = new Date(r.starts_at);
        const key = new Date(d.getFullYear(), d.getMonth(), d.getDate()).toISOString();
        (map[key] ||= []).push(r);
    }
    return Object.entries(map).sort(([a],[b]) => +new Date(a) - +new Date(b));
}

export default function ScheduleBoard(){
    const [rows, setRows] = useState<Row[]|null>(null);

    useEffect(() => {
        fetch("/api/appointments/window?lawyer_id=1&before_min=60&after_min=" + (14*24*60))
            .then(r=>r.json()).then(setRows).catch(()=>setRows([]));
    }, []);

    const now = Date.now();
    const groups = useMemo(()=> groupByDate(rows ?? []), [rows]);

    if (rows === null) return <p>読み込み中…</p>;
    if (!rows.length) return <p>直近1時間〜今後2週間の予約はありません。</p>;

    return (
        <div style={{display:"grid", gap: 12}}>
            {groups.map(([dateISO, dayRows]) => (
                <div key={dateISO} style={{border:"1px solid #eee", borderRadius:12, padding:12}}>
                    <b>{new Date(dateISO).toLocaleDateString("ja-JP",{weekday:"short", month:"numeric", day:"numeric"})}</b>
                    <ul style={{marginTop:8}}>
                        {dayRows.map((r) => {
                            const t = new Date(r.starts_at).getTime();
                            const mins = Math.floor((t - now) / 60000);
                            // 状態: 過去(<0) 直近(0〜60) 先(>60)
                            let state: "past" | "soon" | "later" = mins < 0 ? "past" : mins <= 60 ? "soon" : "later";
                            return (
                                <li key={r.id} style={{display:"flex",alignItems:"center",gap:8,marginBottom:6}}>
                  <span style={{width:70,opacity:.8}}>
                    {new Date(r.starts_at).toLocaleTimeString("ja-JP",{hour:"2-digit",minute:"2-digit"})}
                  </span>
                                    <span style={{flex:1}}>{r.client_name}</span>
                                    {state === "past" && (
                                        <button disabled style={{opacity:.4, padding:"4px 10px", borderRadius:8}}>終了</button>
                                    )}
                                    {state === "soon" && (
                                        <a href={`/host?aid=${r.id}`}
                                           style={{padding:"4px 10px", borderRadius:8, background:"#ece9ff", textDecoration:"none"}}>
                                            開始
                                        </a>
                                    )}
                                    {state === "later" && (
                                        <a href={`/host?aid=${r.id}`} style={{padding:"4px 10px", borderRadius:8, border:"1px solid #ddd"}}>
                                            詳細
                                        </a>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                </div>
            ))}
        </div>
    );
}
