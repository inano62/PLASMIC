// src/components/HistoryList.tsx
import { useEffect, useMemo, useState } from "react";
import { groupByDate } from "../lib/date";

export default function HistoryList(){
    const [data,setData]=useState<{rows:any[], total:number, page:number, per:number}|null>(null);
    const [page,setPage]=useState(1);
    const [q,setQ]=useState("");

    useEffect(()=>{
        fetch(`/api/appointments/past?lawyer_id=1&days=180&page=${page}&per=50`)
            .then(r=>r.json()).then(setData).catch(()=>setData({rows:[],total:0,page:1,per:50}));
    },[page]);

    if(!data) return <p>読み込み中…</p>;
    const filtered = useMemo(()=>{
        if(!q) return data.rows;
        const s = q.toLowerCase();
        return data.rows.filter(r =>
            (r.client_name||"").toLowerCase().includes(s) ||
            (r.client_email||"").toLowerCase().includes(s) ||
            (r.client_phone||"").toLowerCase().includes(s)
        );
    },[data,q]);

    return (
        <div>
            <div style={{display:"flex",gap:8,marginBottom:10}}>
                <input value={q} onChange={e=>setQ(e.target.value)} placeholder="氏名/メール/電話で検索" />
                <span style={{opacity:.6}}>{data.total}件</span>
            </div>
            {filtered.length===0 ? <p>履歴はありません。</p> :
                groupByDate(filtered).map(([dateISO, rows])=>(
                    <div key={dateISO} style={{border:"1px solid #eee",borderRadius:12,padding:12,marginBottom:10}}>
                        <b>{new Date(dateISO).toLocaleDateString("ja-JP",{weekday:"short",month:"numeric",day:"numeric"})}</b>
                        <table style={{width:"100%",marginTop:8}}>
                            <tbody>
                            {rows.map((r:any)=>(
                                <tr key={r.id}>
                                    <td style={{padding:"6px 4px",width:90}}>
                                        {new Date(r.starts_at).toLocaleTimeString("ja-JP",{hour:"2-digit",minute:"2-digit"})}
                                    </td>
                                    <td style={{padding:"6px 4px"}}>{r.client_name}</td>
                                    <td style={{padding:"6px 4px",opacity:.7}}>{r.client_email||"-"}</td>
                                    <td style={{padding:"6px 4px",opacity:.7}}>{r.client_phone||"-"}</td>
                                    <td style={{padding:"6px 4px",opacity:.7}}>{r.status}</td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    </div>
                ))
            }
            <div style={{display:"flex",gap:8,marginTop:8}}>
                <button onClick={()=>setPage(p=>Math.max(1,p-1))} disabled={page<=1}>前へ</button>
                <span>ページ {page}</span>
                <button onClick={()=>setPage(p=>p+1)} disabled={data.rows.length < data.per}>次へ</button>
            </div>
        </div>
    );
}
