// frontend/src/components/site-builder/HeroEditor.tsx
import { useState } from "react";
import { Button } from "react-bootstrap";
import {jupload} from "../../lib/api";

type HeroData = {
    kicker?: string;
    title?: string;
    subtitle?: string;
    btnText?: string;
    btnHref?: string;
    imgId?: number | null;
    bgUrl?: string;
    avatarUrl?: string;
};

export default function HeroEditor({
                                       data,
                                       onSave,
                                   }: {
    data: any;
    onSave: (diff: any) => void;
}) {
    const [v, setV] = useState<HeroData>({
        kicker: data?.kicker ?? "",
        title: data?.title ?? "",
        subtitle: data?.subtitle ?? "",
        btnText: data?.btnText ?? "",
        btnHref: data?.btnHref ?? "",
        imgId: data?.imgId ?? null,
        bgUrl: data?.bgUrl ?? "",
        avatarUrl: data?.avatarUrl ?? "",
    });
    const [busy, setBusy] = useState(false);
    const [err, setErr] = useState<string | null>(null);
    // ← File を受け取る関数
    async function handleSave() {
        setBusy(true);
        try {
            const next = { ...v };
            console.log("[HeroEditor] save click payload:", next);
            await onSave(v);

        } finally {
            setBusy(false);
        }
    }
    async function handleUpload(file?: File, field: "bgUrl" | "avatarUrl" = "bgUrl") {
        if (!file) return;
        setBusy(true); setErr(null);
        try {
            const fd = new FormData(); fd.append("file", file);
            const json = await jupload("admin/media", fd);

            setV(prev => {

                const next = { ...prev, [field]: json.url };
                onSave(next);
                return next;
            });
        } catch (e:any) {
            setErr(e.message || "アップロードに失敗しました");
        } finally { setBusy(false); }
    }


    return (
        <div className="row g-3">
            {["kicker","title","subtitle","btnText","btnHref"].map(k=>(
                <div className={k==="subtitle" ? "col-12" : "col-md-6"} key={k}>
                    <label className="form-label">{k}</label>
                    <input className="form-control"
                           value={(v as any)[k] || ""}
                           onChange={e=>setV({ ...v, [k]: e.target.value })}/>
                </div>
            ))}

            {/* 背景画像 */}
            <div className="col-12">
                <label className="form-label">背景画像</label>
                <input type="file" className="form-control" accept="image/*"
                       onChange={e=>handleUpload(e.target.files?.[0], "bgUrl")}
                       disabled={busy}/>
                {err && <div className="text-danger small mt-2">{err}</div>}
                {v.bgUrl && <img src={v.bgUrl} className="img-fluid mt-2 rounded-3" alt="" />}
            </div>

            {/* アバター */}
            <div className="col-12">
                <label className="form-label">アバター画像</label>
                <input type="file" className="form-control" accept="image/*"
                       onChange={e=>handleUpload(e.target.files?.[0], "avatarUrl")}
                       disabled={busy}/>
                {v.avatarUrl && (
                    <img src={v.avatarUrl} className="img-thumbnail mt-2 rounded-circle"
                         style={{width:120, height:120, objectFit:"cover"}} alt=""/>
                )}
            </div>

            <div className="col-12">
                <Button onClick={handleSave} disabled={busy}>このブロックを保存</Button>
            </div>
        </div>
    );
}
