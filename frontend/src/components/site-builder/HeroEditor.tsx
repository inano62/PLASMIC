// frontend/src/components/site-builder/HeroEditor.tsx
import { useState } from "react";
import { Button } from "react-bootstrap";
import API from "@/lib/api";

export default function HeroEditor({
                                       data, onSave,
                                   }:{ data:any; onSave:(diff:any)=>void }) {
    const [v,setV] = useState<any>({
        kicker: data?.kicker ?? "", title: data?.title ?? "", subtitle: data?.subtitle ?? "",
        btnText: data?.btnText ?? "", btnHref: data?.btnHref ?? "",
        imgId: data?.imgId ?? null, imgUrl: data?.imgUrl ?? "",
    });

    async function onPick(e: React.ChangeEvent<HTMLInputElement>) {
        const f = e.target.files?.[0]; if (!f) return;
        const fd = new FormData(); fd.append("file", f);
        const uploaded = await API.jupload<{id:number; url:string}>("/media", fd);
        setV({ ...v, imgId: uploaded.id, imgUrl: uploaded.url });
    }

    return (
        <div className="row g-3">
            {["kicker","title","subtitle","btnText","btnHref"].map(k=>(
                <div className={k==="subtitle" ? "col-12" : "col-md-6"} key={k}>
                    <label className="form-label">{k}</label>
                    <input className="form-control" value={v[k]||""}
                           onChange={e=>setV({...v,[k]:e.target.value})}/>
                </div>
            ))}
            <div className="col-md-6">
                <label className="form-label">ヒーロー画像</label>
                <input type="file" className="form-control" accept="image/*" onChange={onPick}/>
                {v.imgUrl && <img src={v.imgUrl} className="img-fluid mt-2 rounded-3" />}
            </div>
            <div className="col-12">
                <Button onClick={()=>onSave(v)}>このブロックを保存</Button>
            </div>
        </div>
    );
}
