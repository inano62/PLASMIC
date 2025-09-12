// frontend/src/components/site-builder/HeroEditor.tsx
import { useState } from "react";
import { Button } from "react-bootstrap";
import API from "@/lib/api";

type HeroData = {
    kicker?: string;
    title?: string;
    subtitle?: string;
    btnText?: string;
    btnHref?: string;
    imgId?: number | null;
    imgUrl?: string;
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
        imgUrl: data?.imgUrl ?? "",
    });
    const [busy, setBusy] = useState(false);
    const [err, setErr] = useState<string | null>(null);

    // ← File を受け取る関数
    async function handleUpload(file?: File) {
        if (!file) return;
        setErr(null);
        setBusy(true);
        try {
            const fd = new FormData();
            fd.append('file', file);

            // ← ここで JSON を直接受け取る。res.ok/res.text は扱わない
            const json = await API.jupload<{ id:number; url:string }>('/media', fd);

            setV(prev => ({ ...prev, imgId: json.id, imgUrl: json.url }));
        } catch (e:any) {
            setErr(e.message || 'アップロードに失敗しました');
        } finally {
            setBusy(false);
        }
    }

    return (
        <div className="row g-3">
            {["kicker", "title", "subtitle", "btnText", "btnHref"].map((k) => (
                <div className={k === "subtitle" ? "col-12" : "col-md-6"} key={k}>
                    <label className="form-label">{k}</label>
                    <input
                        className="form-control"
                        value={(v as any)[k] || ""}
                        onChange={(e) => setV({ ...v, [k]: e.target.value })}
                    />
                </div>
            ))}

            <div className="col-md-6">
                <label className="form-label">ヒーロー画像</label>
                <input
                    type="file"
                    className="form-control"
                    accept="image/*"
                    // ★ ここで File を取り出して渡す
                    onChange={(e) => handleUpload(e.target.files?.[0] || undefined)}
                    disabled={busy}
                />
                {err && <div className="text-danger small mt-2">{err}</div>}
                {v.imgUrl && (
                    <img src={v.imgUrl} className="img-fluid mt-2 rounded-3" />
                )}
            </div>

            <div className="col-12">
                <Button onClick={() => onSave(v)} disabled={busy}>
                    このブロックを保存
                </Button>
            </div>
        </div>
    );
}
