import { useState } from "react";
import { Button } from "react-bootstrap";
import API from "@/lib/api";

type MediaData = {
    imgUrl?: string;
    caption?: string;
    align?: "center"|"left"|"right";
    width?: number;
    shadow?: boolean;
    rounded?: boolean;
};

export default function MediaEditor({ data, onSave }: { data:any; onSave:(diff:any)=>void }) {
    const [v, setV] = useState<MediaData>({
        imgUrl: data?.imgUrl ?? "",
        caption: data?.caption ?? "",
        align: data?.align ?? "center",
        width: data?.width ?? 680,
        shadow: data?.shadow ?? true,
        rounded: data?.rounded ?? true,
    });
    const [busy,setBusy] = useState(false);
    const [err,setErr] = useState<string|null>(null);

    async function handleUpload(file?: File) {
        if (!file) return;
        setBusy(true); setErr(null);
        try {
            const fd = new FormData();
            fd.append("file", file);
            const res = await API.jupload<{id:number; url:string}>("/media", fd);
            setV(prev => ({...prev, imgUrl: res.url}));
        } catch (e:any) {
            setErr(e.message || "アップロードに失敗しました");
        } finally { setBusy(false); }
    }

    return (
        <div className="row g-3">
            <div className="col-12">
                <label className="form-label">画像ファイル</label>
                <input type="file" className="form-control" accept="image/*"
                       onChange={(e)=>handleUpload(e.target.files?.[0] || undefined)}
                       disabled={busy}/>
                {err && <div className="text-danger small mt-2">{err}</div>}
                {v.imgUrl && <img src={v.imgUrl} className="img-fluid mt-2 rounded-3" />}
            </div>

            <div className="col-md-6">
                <label className="form-label">キャプション</label>
                <input className="form-control" value={v.caption||""}
                       onChange={(e)=>setV({...v, caption:e.target.value})}/>
            </div>

            <div className="col-md-3">
                <label className="form-label">配置</label>
                <select className="form-select" value={v.align}
                        onChange={(e)=>setV({...v, align: e.target.value as any})}>
                    <option value="center">中央</option>
                    <option value="left">左寄せ</option>
                    <option value="right">右寄せ</option>
                </select>
            </div>

            <div className="col-md-3">
                <label className="form-label">最大幅(px)</label>
                <input type="number" className="form-control" value={v.width||680}
                       onChange={(e)=>setV({...v, width: Number(e.target.value)||680})}/>
            </div>

            <div className="col-md-3">
                <div className="form-check mt-4">
                    <input className="form-check-input" type="checkbox" checked={!!v.shadow}
                           onChange={(e)=>setV({...v, shadow:e.target.checked})} id="m-shadow"/>
                    <label className="form-check-label" htmlFor="m-shadow">影を付ける</label>
                </div>
            </div>
            <div className="col-md-3">
                <div className="form-check mt-4">
                    <input className="form-check-input" type="checkbox" checked={!!v.rounded}
                           onChange={(e)=>setV({...v, rounded:e.target.checked})} id="m-rounded"/>
                    <label className="form-check-label" htmlFor="m-rounded">角丸</label>
                </div>
            </div>

            <div className="col-12">
                <Button onClick={()=>onSave(v)} disabled={busy}>このブロックを保存</Button>
            </div>
        </div>
    );
}
