// src/components/BookingForm.tsx
import { useEffect, useMemo, useState,useRef } from "react";
import {API} from "../lib/api.ts";

function toLocalDatetimeValue(iso?: string) {
    if (!iso) return "";
    const d = new Date(iso);
    const p = (n:number)=>String(n).padStart(2,"0");
    return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
}
function getVisitorId(){
    let id = localStorage.getItem("visitor_id");
    if(!id){ id = crypto.randomUUID(); localStorage.setItem("visitor_id", id); }
    return id;
}
// function LookupBox(){
//     const [identifier,setIdentifier]=useState("");
//     const [result,setResult]=useState<any|null>(null);
//     const [err,setErr]=useState<string|undefined>();
//     async function onLookup(){
//         setErr(undefined); setResult(null);
//         const res=await fetch("/api/my/appointments/lookup",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({identifier})});
//         const j=await res.json();
//         if(!res.ok){ setErr(j?.message || "見つかりませんでした"); return; }
//         setResult(j.latest);
//     }
//     return (
//         <div className="rounded-xl border p-3 mb-4">
//             <div className="text-sm mb-2">予約した覚えの確認（電話番号・メール・お名前のいずれか）</div>
//             <div className="flex gap-2">
//                 <input value={identifier} onChange={e=>setIdentifier(e.target.value)} placeholder="例: 08012345678 / you@example.com / 山田太郎" className="flex-1"/>
//                 <button onClick={onLookup}>検索</button>
//             </div>
//             {err && <div className="text-red-600 text-sm mt-2">{err}</div>}
//             {result && (
//                 <div className="text-sm mt-2">
//                     直近の予約：{new Date(result.start_at).toLocaleString()}（{result.purpose_title}）<br/>
//                     入室リンク：<a href={result.clientJoinPath}>{location.origin + result.clientJoinPath}</a>
//                 </div>
//             )}
//         </div>
//     );
// }




export default function BookingForm(
    { lawyerId, defaultStartsAt }: { lawyerId:number; defaultStartsAt?: string }
){
    const [name, setName] = useState("");
    const [email, setEmail] = useState("");
    const [phone, setPhone] = useState("");
    const [purposeTitle, setPurposeTitle]   = useState("");
    const [purposeDetail, setPurposeDetail] = useState("");
    // const [startsAt, setStartsAt] = useState<string>(toLocalDatetimeValue(defaultStartsAt) || nowLocalDatetimeValue());
    useEffect(()=> setStartsAt(toLocalDatetimeValue(defaultStartsAt) || startsAt), [defaultStartsAt]);
    const [submitting, setSubmitting] = useState(false);
    const abortRef = useRef<AbortController | null>(null);
    const [error, setError] = useState<string|undefined>();
    const [done, setDone] = useState<{when:string, clientPath:string, hostPath:string}|null>(null);
// 初期値：次の30分
    const [startsAt, setStartsAt] = useState<string>(
        defaultStartsAt ? toLocalDatetimeValue(defaultStartsAt) : next30minLocalValue()
    );
    // リロード復元（直近の自分の予約を再取得）
    useEffect(()=> {
        const vid = getVisitorId();
        fetch(`/api/my/appointments?visitor_id=${vid}`)
            .then(r=>r.ok?r.json():[])
            .then((rows:any[])=>{
                if(rows?.[0]) {
                    const r = rows[0];
                    setDone({ when: toLocalDatetimeValue(r.starts_at)!, clientPath:`/wait?aid=${r.id}`, hostPath:`/host?aid=${r.id}` });
                }
            }).catch(()=>{});
    }, []);
    useEffect(() => {
        if (defaultStartsAt) {
            const d = new Date(defaultStartsAt);
            setStartsAt(next30minLocalValue(d));
        }
    }, [defaultStartsAt]);

    const canSubmit = useMemo(()=>{
        if(!name || !purposeTitle || !startsAt) return false;
        const sel = new Date(startsAt).getTime();
        return sel >= Date.now() + 4*60*1000;
    }, [name, purposeTitle, startsAt]);
    async function submit(e:React.FormEvent){
        e.preventDefault();
        if(!canSubmit || submitting) return;
        abortRef.current?.abort();
        const ac = new AbortController();
        abortRef.current = ac;

        setSubmitting(true); setError(undefined);
        try{
            const payload = {
                lawyer_id: lawyerId,
                client_name: name,
                client_email: email,
                client_phone: phone,
                start_at: new Date(startsAt).toISOString(),
                visitor_id: getVisitorId(),
                purpose_title: purposeTitle,
                purpose_detail: purposeDetail,
            };
            const res = await fetch(`/api/appointments`,{
                method:"POST",
                headers:{
                    "Content-Type":"application/json",
                    "Accept": "application/json",
                },
                body: JSON.stringify(payload),
                signal: ac.signal,
            });
            const ct = res.headers.get("content-type") || "";
            const body = ct.includes("application/json") ? await res.json() : await res.text();

            if (!res.ok) {
                if (res.status === 409 && (body as any)?.suggested_start_at) {
                    setStartsAt(toLocalDatetimeValue((body as any).suggested_start_at));
                    setError("その時間は埋まっていました。次の空き時間に調整しました。もう一度「予約する」を押してください。");
                    return;
                }
                throw new Error(typeof body === "string" ? body : (body as any)?.message || "予約に失敗しました");
            }

            // ✅ 成功時：上書き禁止。1回だけセット
            const data = body as { appointmentId: string; clientJoinPath?: string; hostJoinPath?: string };
            console.log("POST /api/appointments body =", body);
            if (!data.clientJoinPath || !data.hostJoinPath) {
                console.warn("Unexpected response shape:", data); // デバッグ用
            }
            setDone({ when: startsAt, clientPath: data.clientJoinPath || "", hostPath: data.hostJoinPath || "" });
            localStorage.setItem("last_aid", String(data.appointmentId));
        } catch (err: any) {
            setError(String(err?.message || err));
        } finally {
            setSubmitting(false);
        }
    }
// スロット再取得
    const [slots, setSlots] = useState<string[]>([]);
    const refreshSlots = () => {
        const from = new Date().toISOString();
        fetch(`/api/availability?from=${encodeURIComponent(from)}&days=7&duration=30`)
            .then(r => r.ok ? r.json() : { slots: [] })
            .then(j => setSlots(j.slots || []))
            .catch(() => setSlots([]));
    };
    useEffect(refreshSlots, []);
    if (done) {
        const fullClient = location.origin + done.clientPath;
        const fullHost   = location.origin + done.hostPath;
        return (
            <div style={{background:"#f7f7ff",padding:12,borderRadius:12,marginBottom:12}}>
                <div>予約を受け付けました：<b>{done.when.replace("T"," ")}</b></div>
                <div style={{marginTop:8,lineHeight:1.7}}>
                    顧客入室URL：<a href={fullClient}>{fullClient}</a><br/>
                    士業入室URL：<a href={fullHost}>{fullHost}</a>
                </div>
            </div>
        );
    }
    function roundUpToNext30Min(d: Date) {
        const x = new Date(d);
        x.setSeconds(0, 0);
        const m = x.getMinutes();
        const r = m % 30 === 0 ? 0 : 30 - (m % 30);
        x.setMinutes(m + r);
        return x;
    }
    function next30minLocalValue(from?: Date) {
        const base = from ?? new Date();
        const x = roundUpToNext30Min(base);
        const p = (n:number)=>String(n).padStart(2,"0");
        return `${x.getFullYear()}-${p(x.getMonth()+1)}-${p(x.getDate())}T${p(x.getHours())}:${p(x.getMinutes())}`;
    }
    function SlotsGrid({ setStartsAt }: { setStartsAt: (v: string)=>void }) {
        const [slots, setSlots] = useState<string[]>([]);
        const fetched = useRef(false);

        useEffect(() => {
            if (fetched.current) return;
            fetched.current = true;
            const from = new Date().toISOString();
            fetch(`/api/availability?from=${encodeURIComponent(from)}&days=7&duration=30`)
                .then(r=>r.json()).then(j=>setSlots(j.slots||[]))
                .catch(()=>setSlots([]));
        }, []);

        return (
            <div className="mt-6">
                <div className="text-sm text-slate-500 mb-2">空き枠（30分刻み・1週間）</div>
                <div className="grid grid-cols-4 gap-3">
                    {slots.map(iso => {
                        const d = new Date(iso);
                        const label = d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
                        return (
                            <button
                                key={iso}
                                type="button"
                                className="px-3 py-2 rounded-xl border hover:bg-slate-50"
                                onClick={() => setStartsAt(toLocalDatetimeValue(iso))}
                            >
                                {d.toLocaleDateString()}
                                {label}
                            </button>
                        );
                    })}
                    {slots.length === 0 && <div className="text-slate-400">空き枠がありません</div>}
                </div>
            </div>
        );
    }

    return (
        <form onSubmit={submit}
              style={{display:"grid",gridTemplateColumns:"1fr 1fr 1fr 1fr auto",gap:8, alignItems:"center"}}>
            <input value={name} onChange={e=>setName(e.target.value)} name="name" placeholder="お名前" required />
            <input value={email} onChange={e=>setEmail(e.target.value)} name="email" placeholder="メール（任意）" />
            <input value={phone} onChange={e=>setPhone(e.target.value)} name="phone" placeholder="電話（任意）" />
            <div style={{gridColumn:"1 / 3"}}>
                <label className="block text-sm mb-1">相談の目的（タイトル）</label>
                <input value={purposeTitle}
                       onChange={e=>setPurposeTitle(e.target.value)}
                       placeholder="例：相続登記の相談"
                       required />
            </div>

            <div style={{gridColumn:"1 / 4"}}>
                <label className="block text-sm mb-1">相談内容の詳細</label>
                <textarea value={purposeDetail}
                          onChange={e=>setPurposeDetail(e.target.value)}
                          placeholder="背景や希望など"
                          rows={5} />
            </div>

            <div>
                <label className="block text-sm mb-1">開始日時</label>
                <input
                    name="start_at"
                    type="datetime-local"
                    value={startsAt}
                    readOnly
                    className="opacity-50 pointer-events-none"
                />
            </div>
            <button type="submit"
                    disabled={!canSubmit || submitting}
                    style={{
                        padding:"10px 14px",
                        borderRadius:12,
                        border:"none",
                        background: (!canSubmit||submitting) ? "#c9c9d6" : "#6c63ff",
                        color:"#fff", cursor: (!canSubmit||submitting) ? "not-allowed" : "pointer",
                        boxShadow:"0 3px 8px rgba(0,0,0,.08)"
                    }}
                    onMouseDown={e=>((e.currentTarget.style.transform="translateY(1px)"))}
                    onMouseUp={e=>((e.currentTarget.style.transform="translateY(0)"))}
                    formNoValidate
            >
                {submitting ? "送信中…" : "予約する"}
            </button>
            {/*<SlotsGrid />*/}
            {error && <div style={{gridColumn:"1 / -1", color:"#c00"}}>{error}</div>}
        </form>

    );
}
