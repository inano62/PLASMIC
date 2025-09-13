import { useEffect, useMemo, useState } from "react";
import { useLocation, useParams } from "react-router-dom";
import API from "../lib/api";

type Tenant = { id: number; display_name: string };
type Pro = { id?: number; user_id?: number; name: string };
type SlotDay = { date: string; slots: string[] };

export default function PublicReserve() {
    const { tenant: tenantFromPath } = useParams();
    const qs = new URLSearchParams(useLocation().search);

    const [tenant, setTenant] = useState<Tenant | null>(null);
    const [pros, setPros] = useState<Pro[]>([]);
    const [lawyerId, setLawyerId] = useState<number | null>(null);
    const [days, setDays] = useState<SlotDay[]>([]);
    const [slot, setSlot] = useState<string>("");

    const [name, setName] = useState("");
    const [email, setEmail] = useState("");
    const [preferredNote, setPreferredNote] = useState("");
    const [submitting, setSubmitting] = useState(false);
    const [result, setResult] = useState<{ host: string; guest: string; starts_at?: string } | null>(null);

    // 事務所を URL から確定
    useEffect(() => {
        (async () => {
            const raw = tenantFromPath || qs.get("slug") || qs.get("tenant") || qs.get("tenant_id") || "";
            if (!raw) return;

            let resolved: { id: number; display_name?: string } | null = null;

            if (/^\d+$/.test(raw)) {
                resolved = { id: Number(raw) };
            } else {
                resolved = await API.get<{ id:number; display_name?:string }>(`public/tenants/resolve?slug=${encodeURIComponent(raw)}`)
                    .catch(async () => API.get<{ id:number; display_name?:string }>(`public/tenants/resolve?key=${encodeURIComponent(raw)}`))
                    .catch(() => null);
            }

            if (!resolved) throw new Error("tenant not found");
            setTenant({ id: resolved.id, display_name: resolved.display_name ?? "" });
        })().catch((e) => console.error(e));
    }, []); // once

    // 先生一覧の取得 & 自動選択
    useEffect(() => {
        if (!tenant) return;
        (async () => {
            const rows = await API.get<Pro[]>(`public/tenants/${tenant.id}/pros`).catch(() => []);
            setPros(rows);

            const fromUrl = qs.get("lawyer_id");
            if (fromUrl && /^\d+$/.test(fromUrl)) {
                setLawyerId(Number(fromUrl));
            } else if (rows[0]) {
                // ← id と user_id のどちらでも拾う
                setLawyerId((rows[0].user_id ?? rows[0].id) ?? null);
            }
        })();
    }, [tenant?.id]);

    // 空き枠
    useEffect(() => {
        if (!tenant || !lawyerId) { setDays([]); setSlot(""); return; }

        (async () => {
            const resp: any = await API.get<any>(`public/tenants/${tenant.id}/slots?lawyer_id=${lawyerId}`)
                .catch(() => null);

            const rows = Array.isArray(resp) ? resp : (resp?.days ?? []);
            const normalized: SlotDay[] = rows.map((d: any) => ({
                date: d.date,
                // times / slots どちらでもOKにする
                slots: d.slots ?? d.times ?? [],
            }));

            setDays(normalized);
        })();
    }, [tenant?.id, lawyerId]);

    const canSubmit = useMemo(
        () => !!tenant?.id && !!lawyerId && !!slot && !!name && /\S+@\S+/.test(email),
        [tenant?.id, lawyerId, slot, name, email]
    );

    async function submit() {
        try {
            if (!tenant?.id || !lawyerId || !slot) return;
            setSubmitting(true);

            // 顧客 upsert
            const cu = await API.post<{ user_id: number }>(`clients/upsert`, { name, email });

            // 予約作成（テナント版）
            const payload = {
                lawyer_id: lawyerId,
                client_name: name,
                client_email: email,
                start_at: new Date(slot).toISOString(),
                starts_at: new Date(slot).toISOString(),
                visitor_id: String(cu.user_id ?? "public"),
                purpose_title: "オンライン相談",
                purpose_detail: preferredNote, // ← 任意希望をここへ
            };

            const ap = await API.post<{ clientJoinPath: string; hostJoinPath: string }>(
                `tenants/${tenant.id}/appointments`,
                payload
            );

            setResult({ host: ap.hostJoinPath, guest: ap.clientJoinPath, starts_at: slot });
        } catch (e:any) {
            alert(e?.message ?? "予約に失敗しました");
            console.error(e);
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="container py-4" style={{ maxWidth: 900 }}>
            <h2 className="mb-3">面談予約</h2>

            {/* 事務所 表示のみ */}
            <div className="card mb-3">
                <div className="card-header">事務所</div>
                <div className="card-body">
                    <div className="text-muted">(この事務所の予約ページ)</div>
                    <div className="mt-2">{tenant ? (tenant.display_name || `#${tenant.id}`) : "事務所の確認中です…"}</div>
                </div>
            </div>

            {/* 日時 */}
            <div className="card mb-3">
                <div className="card-header">1. 日時を選ぶ</div>
                <div className="card-body">
                    {!tenant && <div className="text-muted">事務所の確認中です…</div>}
                    {tenant && !lawyerId && <div className="text-muted">担当者を自動選択中…</div>}
                    {tenant && lawyerId && days.length === 0 && <div className="text-muted">読み込み中、または空き枠がありません。</div>}

                    {days.map(d => (
                        <div key={d.date} className="mb-2">
                            <div className="fw-semibold mb-1">{d.date}</div>
                            <div className="d-flex flex-wrap gap-2">
                                {d.slots.map(s => (
                                    <button
                                        key={s}
                                        type="button"
                                        className={"btn btn-sm " + (slot === s ? "btn-primary" : "btn-outline-secondary")}
                                        onClick={() => setSlot(s)}
                                    >
                                        {new Date(s).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}
                                    </button>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* お客様情報 */}
            <div className="card mb-3">
                <div className="card-header">2. お客様情報</div>
                <div className="card-body">
                    <div className="row g-2">
                        <div className="col-md-6">
                            <label className="form-label">お名前</label>
                            <input className="form-control" value={name} onChange={(e) => setName(e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label">メール</label>
                            <input className="form-control" value={email} onChange={(e) => setEmail(e.target.value)} />
                        </div>
                    </div>

                    <div className="mt-3">
                        <label className="form-label">担当者の希望・メモ（任意）</label>
                        <input
                            className="form-control"
                            placeholder="例）女性希望 / ○○さん希望 / 事情メモ など"
                            value={preferredNote}
                            onChange={(e) => setPreferredNote(e.target.value)}
                        />
                    </div>
                </div>
            </div>

            <div className="d-flex gap-2">
                <button className="btn btn-primary" disabled={!canSubmit || submitting} onClick={submit}>
                    {submitting ? "予約中…" : "予約を確定する"}
                </button>
            </div>

            {result && (
                <div className="alert alert-success mt-3">
                    <div className="mb-1">予約を受け付けました。開始時刻：{new Date(result.starts_at!).toLocaleString()}</div>
                    <div>ゲスト用リンク：<a href={result.guest} target="_blank" rel="noreferrer">{result.guest}</a></div>
                    <div>ホスト用リンク：<a href={result.host} target="_blank" rel="noreferrer">{result.host}</a></div>
                </div>
            )}
        </div>
    );
}
