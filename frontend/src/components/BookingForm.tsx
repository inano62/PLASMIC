// src/components/BookingForm.tsx
import { useEffect, useMemo, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { jget, jpost } from '../lib/api';
function toLocalDatetimeValue(iso?: string) {
    if (!iso) return "";
    const d = new Date(iso);
    const p = (n: number) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(
        d.getHours()
    )}:${p(d.getMinutes())}`;
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
    const p = (n: number) => String(n).padStart(2, "0");
    return `${x.getFullYear()}-${p(x.getMonth() + 1)}-${p(x.getDate())}T${p(
        x.getHours()
    )}:${p(x.getMinutes())}`;
}
function getVisitorId() {
    let id:string|number|null = localStorage.getItem("visitor_id");
    if (!id) {
        // id = crypto.randomUUID();
        // localStorage.setItem("visitor_id", id);
        id = 1;
    }
    return id;
}

export default function BookingForm({
                                        tenant,
                                        lawyerId,
                                        defaultStartsAt,
                                    }: {
    tenant: string;
    lawyerId: number;
    defaultStartsAt?: string;
}) {
    const [name, setName] = useState("");
    const [email, setEmail] = useState("");
    const [phone, setPhone] = useState("");
    const [purposeTitle, setPurposeTitle] = useState("");
    const [purposeDetail, setPurposeDetail] = useState("");
    const [startsAt, setStartsAt] = useState<string>(
        defaultStartsAt ? toLocalDatetimeValue(defaultStartsAt) : next30minLocalValue()
    );
    useEffect(() => {
        if (defaultStartsAt) {
            const d = new Date(defaultStartsAt);
            setStartsAt(next30minLocalValue(d));
        }
    }, [defaultStartsAt]);

    const [submitting, setSubmitting] = useState(false);
    const abortRef = useRef<AbortController | null>(null);
    const [error, setError] = useState<string | undefined>();
    const [done, setDone] = useState<{
        when: string;
        clientPath: string;
        hostPath: string;
    } | null>(null);

    // 直近の自分の予約を復元
    useEffect(() => {
        const vid = getVisitorId();
        fetch(`/api/tenants/${tenant}/my/appointments?visitor_id=${vid}`)
            .then((r) => (r.ok ? r.json() : []))
            .then((rows: any[]) => {
                if (rows?.[0]) {
                    const r = rows[0];
                    setDone({
                        when: toLocalDatetimeValue(r.starts_at)!,
                        clientPath: `/wait?aid=${r.id}`,
                        hostPath: `/host?aid=${r.id}`,
                    });
                }
            })
            .catch(() => {});
    }, [tenant]);

    const canSubmit = useMemo(() => {
        if (!name || !purposeTitle || !startsAt) return false;
        const sel = new Date(startsAt).getTime();
        return sel >= Date.now() + 4 * 60 * 1000;
    }, [name, purposeTitle, startsAt]);

    async function submit(e: React.FormEvent) {
        e.preventDefault();
        if (!canSubmit || submitting) return;

        abortRef.current?.abort();
        const ac = new AbortController();
        abortRef.current = ac;

        setSubmitting(true);
        setError(undefined);
        try {
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

            const res = await fetch(`/api/tenants/${tenant}/appointments`, {
                method: "POST",
                headers: { "Content-Type": "application/json", Accept: "application/json" },
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
                throw new Error(
                    typeof body === "string" ? body : (body as any)?.message || "予約に失敗しました"
                );
            }

            const data = body as {
                appointmentId: string;
                clientJoinPath?: string;
                hostJoinPath?: string;
            };
            setDone({
                when: startsAt,
                clientPath: data.clientJoinPath || "",
                hostPath: data.hostJoinPath || "",
            });
            localStorage.setItem("last_aid", String(data.appointmentId));
        } catch (err: any) {
            setError(String(err?.message || err));
        } finally {
            setSubmitting(false);
        }
    }

    // 完了表示（URL には tenant プレフィックスを付ける）
    if (done) {
        const base = `${location.origin}/${tenant}`;
        const fullClient = `${base}${done.clientPath}`;
        const fullHost = `${base}${done.hostPath}`;
        return (
            <div style={{ background: "#f7f7ff", padding: 12, borderRadius: 12, marginBottom: 12 }}>
                <div>
                    予約を受け付けました：<b>{done.when.replace("T", " ")}</b>
                </div>
                <Link to={`/${tenant}/reserve`}>面談予約</Link>
                <div style={{ marginTop: 8, lineHeight: 1.7 }}>
                    顧客入室URL：<a href={fullClient}>{fullClient}</a>
                    <br />
                    士業入室URL：<a href={fullHost}>{fullHost}</a>
                </div>
            </div>
        );
    }

    return (
        <form
            onSubmit={submit}
            style={{
                display: "grid",
                gridTemplateColumns: "1fr 1fr 1fr 1fr auto",
                gap: 8,
                alignItems: "center",
            }}
        >
            <input value={name} onChange={(e) => setName(e.target.value)} name="name" placeholder="お名前" required />
            <input value={email} onChange={(e) => setEmail(e.target.value)} name="email" placeholder="メール（任意）" />
            <input value={phone} onChange={(e) => setPhone(e.target.value)} name="phone" placeholder="電話（任意）" />

            <div style={{ gridColumn: "1 / 3" }}>
                <label className="block text-sm mb-1">相談の目的（タイトル）</label>
                <input
                    value={purposeTitle}
                    onChange={(e) => setPurposeTitle(e.target.value)}
                    placeholder="例：相続登記の相談"
                    required
                />
            </div>

            <div style={{ gridColumn: "1 / 4" }}>
                <label className="block text-sm mb-1">相談内容の詳細</label>
                <textarea
                    value={purposeDetail}
                    onChange={(e) => setPurposeDetail(e.target.value)}
                    placeholder="背景や希望など"
                    rows={5}
                />
            </div>

            <div>
                <label className="block text-sm mb-1">開始日時</label>
                <input name="start_at" type="datetime-local" value={startsAt} readOnly className="opacity-50 pointer-events-none" />
            </div>

            <button
                type="submit"
                disabled={!canSubmit || submitting}
                style={{
                    padding: "10px 14px",
                    borderRadius: 12,
                    border: "none",
                    background: !canSubmit || submitting ? "#c9c9d6" : "#6c63ff",
                    color: "#fff",
                    cursor: !canSubmit || submitting ? "not-allowed" : "pointer",
                    boxShadow: "0 3px 8px rgba(0,0,0,.08)",
                }}
                onMouseDown={(e) => (e.currentTarget.style.transform = "translateY(1px)")}
                onMouseUp={(e) => (e.currentTarget.style.transform = "translateY(0)")}
                formNoValidate
            >
                {submitting ? "送信中…" : "予約する"}
            </button>

            {error && <div style={{ gridColumn: "1 / -1", color: "#c00" }}>{error}</div>}
        </form>
    );
}
