import { useEffect, useState } from "react";
import BookingForm from "../components/BookingForm";

export default function Reserve(){
    const [days, setDays] = useState<any[]>([]);
    const [prefill, setPrefill] = useState<string | null>(
        new URLSearchParams(location.search).get("prefill")
    );
    useEffect(()=>{ fetch("/api/timeslots?lawyer_id=1&days=7").then(r=>r.json()).then(setDays); },[]);

    return (
        <div style={{padding:16}}>

            <h2>オンライン相談の予約</h2>
            <BookingForm lawyerId={1} defaultStartsAt={prefill ?? undefined} />

            <h3 style={{marginTop:16}}>空き枠（30分刻み・1週間）</h3>
            <div style={{display:"grid",gridTemplateColumns:"repeat(auto-fit,minmax(220px,1fr))",gap:12}}>
                {days.map((d:any)=>(
                    <div key={d.date} style={{border:"1px solid #eee",borderRadius:12,padding:12}}>
                        <strong>{new Date(d.date).toLocaleDateString("ja-JP",{weekday:"short",month:"numeric",day:"numeric"})}</strong>
                        <div style={{display:"flex",flexWrap:"wrap",gap:8,marginTop:8}}>
                            {d.slots.map((s:string)=>(
                                <button key={s} type="button"
                                        onClick={()=> setPrefill(s)}
                                        style={{padding:"6px 10px",borderRadius:10,border:"1px solid #ddd"}}>
                                    {new Date(s).toLocaleTimeString("ja-JP",{hour:"2-digit",minute:"2-digit"})}
                                </button>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
