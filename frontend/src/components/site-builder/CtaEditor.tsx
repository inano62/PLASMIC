import {useState} from "react";
import {Button} from "react-bootstrap";

export default function CtaEditor({data, onSave}:{data:any; onSave:(diff:any)=>void}) {
    const [v,setV]=useState<any>({ text:data?.text||"", btnText:data?.btnText||"", btnHref:data?.btnHref||"" });
    return (
        <div className="row g-3">
            {["text","btnText","btnHref"].map(k=>(
                <div className="col-md-6" key={k}>
                    <label className="form-label">{k}</label>
                    <input className="form-control" value={v[k]||""}
                           onChange={e=>setV({...v, [k]:e.target.value})}/>
                </div>
            ))}
            <div className="col-12"><Button onClick={()=>onSave(v)}>このブロックを保存</Button></div>
        </div>
    );
}