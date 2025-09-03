// src/components/admin/IntakePanel.tsx
import { useState } from "react";

type Msg = { id:number; from:"client"|"admin"|"system"; text:string; at:string };
type Inquiry = {
    id:number; name:string; email:string; topic:string; message:string;
    status:"new"|"approved"|"declined"|"spam";
};

const templates = {
    askMore: "状況把握のため、①対象の不動産/案件、②期限の有無、③希望の対応方法を教えてください。",
    approve: "ご相談内容は当事務所の業務範囲です。ビデオ面談で詳しく伺います。事前に身分証をご用意ください。",
    decline: "恐れ入りますが、本件は弁護士の専門領域です。当事務所では対応できません。弁護士会の法律相談等をご検討ください。",
};

export default function IntakePanel({
                                        onApprove,
                                    }: { onApprove?: (inq: Inquiry) => void }) {
    const [inq, setInq] = useState<Inquiry>({
        id: 1,
        name: "田中 太郎",
        email: "taro@example.com",
        topic: "相続登記",
        message: "父が亡くなり相続登記の進め方を知りたいです。",
        status: "new",
    });
    const [msgs, setMsgs] = useState<Msg[]>([
        { id: 1, from: "client", text: inq.message, at: new Date().toISOString() },
    ]);
    const [draft, setDraft] = useState("");

    const add = (m: Msg) => setMsgs((x) => [...x, m]);
    const send = (text: string, from: Msg["from"] = "admin") => {
        add({ id: Date.now(), from, text, at: new Date().toISOString() });
    };

    const approve = () => {
        setInq((s) => ({ ...s, status: "approved" }));
        send("面談にお進みいただけます。予約リンクをお送りします。", "system");
        onApprove?.({ ...inq, status: "approved" });
    };
    const decline = () => {
        setInq((s) => ({ ...s, status: "declined" }));
        send(templates.decline, "admin");
    };

    return (
        <div className="rounded-2xl border bg-white p-4 shadow-sm">
            <div className="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div className="fw-semibold">{inq.name}（{inq.topic}）</div>
                    <div className="text-muted small">{inq.email}</div>
                </div>
                <span className="badge bg-secondary text-uppercase">{inq.status}</span>
            </div>

            {/* タイムライン */}
            <div className="intake-timeline border rounded p-3 mb-3">
                {msgs.map(m=>(
                    <div className={`mb-2 ${m.from==="admin" ? "text-end": ""}`}>
                        <div className={`intake-bubble ${m.from}`}>
                            <div className="small">{m.text}</div>
                        </div>
                    </div>
                ))}
            </div>

            {/* 入力 & クイック返信 */}
            <div className="d-flex gap-2 mb-2">
                <input className="form-control flex-grow-1" style={{minWidth:0}} value={draft} onChange={e=>setDraft(e.target.value)} placeholder="返信を入力…" />
                <button className="btn btn-primary flex-shrink-0" onClick={()=>{ if(!draft) return; send(draft,"admin"); setDraft(""); }}>送信</button>
            </div>
            <div className="intake-quick  d-flex flex-wrap gap-2 mb-3">
                <button className="btn btn-outline-secondary btn-sm" onClick={()=>send(templates.askMore,"admin")}>追加質問</button>
                <button className="btn btn-outline-success btn-sm" onClick={()=>send(templates.approve,"admin")}>面談案内</button>
                <button className="btn btn-outline-danger btn-sm" onClick={decline}>丁寧にお断り</button>
                <button className="btn btn-outline-dark btn-sm" onClick={()=>setInq(s=>({...s,status:"spam"}))}>冷やかし</button>
            </div>

            <div className="d-flex gap-2">
                <button className="btn btn-success" disabled={inq.status==="approved"} onClick={approve}>面談へ進める</button>
                <a className="btn btn-outline-primary" href={`/host?aid=${inq.id}`} target="_blank" rel="noreferrer">今すぐビデオ</a>
            </div>
        </div>
    );
}
