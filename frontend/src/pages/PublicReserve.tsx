import { useEffect, useMemo, useState } from "react";

type Tenant = { id: number; name: string };
type Pro = { user_id: number; name: string };
type SlotDay = { date: string; slots: string[] };

async function jget<T>(url: string): Promise<T> {
    const r = await fetch(url, { headers: { Accept: "application/json" } });
    if (!r.ok) {
        const t = await r.text();
        console.error("GET", url, r.status, t);
        throw new Error(t);
    }
    return r.json();
}

async function jpost<T>(url: string, body: any): Promise<T> {
    const r = await fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify(body),
    });
    if (!r.ok) {
        const t = await r.text();
        console.error("POST", url, r.status, t);
        throw new Error(t);
    }
    return r.json();
}

export default function PublicReserve() {
    const [tenants, setTenants] = useState<Tenant[]>([]);
    const [tenantId, setTenantId] = useState<number | "">("");
    const [pros, setPros] = useState<Pro[]>([]);
    const [lawyerId, setLawyerId] = useState<number | "">("");
    const [days, setDays] = useState<SlotDay[]>([]);
    const [slot, setSlot] = useState<string>("");

    const [name, setName] = useState("");
    const [email, setEmail] = useState("");
    const [submitting, setSubmitting] = useState(false);
    const [result, setResult] = useState<{
        host: string;
        guest: string;
        starts_at?: string;
    } | null>(null);

    // booleanに正規化
    const canSubmit = useMemo(
        () => !!tenantId && !!lawyerId && !!slot && !!name && /\S+@\S+/.test(email),
        [tenantId, lawyerId, slot, name, email]
    );

    // 先生一覧
    useEffect(() => {
        jget<Tenant[]>("/api/public/tenants")
            .then(setTenants)
            .catch(() => setTenants([]));
    }, []);

    // 所属の先生一覧
    useEffect(() => {
        if (!tenantId) {
            setPros([]);
            setLawyerId("");
            return;
        }
        jget<Pro[]>(`/api/public/tenants/${tenantId}/pros`)
            .then(setPros)
            .catch(() => {
                setPros([]);
                setLawyerId("");
            });
    }, [tenantId]);

    // 空き枠
    useEffect(() => {
        if (!tenantId || !lawyerId) {
            setDays([]);
            setSlot("");
            return;
        }
        jget<SlotDay[]>(`/api/public/tenants/${tenantId}/slots?lawyer_id=${lawyerId}`)
            .then(setDays)
            .catch(() => setDays([]));
    }, [tenantId, lawyerId]);

    // 送信処理
    async function submit() {
        try {
            if (!tenantId || !lawyerId || !slot) {
                alert("入力が不足しています");
                return;
            }
            setSubmitting(true);

            // 1) 顧客 upsert
            const cu = await jpost<{ user_id: number; magic_url?: string }>(
                "/api/clients/upsert",
                { name, email }
            );

            // 2) 予約作成（BookingForm と同じエンドポイント＆キー名）
            const ap = await jpost<{ clientJoinPath: string; hostJoinPath: string }>(
                `/api/tenants/${String(tenantId)}/appointments`,
                {
                    lawyer_id: Number(lawyerId),
                    client_name: name,
                    client_email: email,
                    start_at: new Date(slot).toISOString(), // ← サーバが start_at を期待
                    visitor_id: String(cu.user_id ?? "public"),
                    purpose_title: "オンライン相談",
                    purpose_detail: "",
                }
            );

            setResult({
                host: ap.hostJoinPath,
                guest: ap.clientJoinPath,
                starts_at: slot,
            });
        } catch (e: any) {
            alert("予約に失敗しました");
            console.error(e);
        } finally {
            setSubmitting(false);
        }
    }
// console.log(await API.post('/api/dev/token', { room, identity, name });)
    return (
        <div className="container py-4" style={{ maxWidth: 900 }}>
            <h2 className="mb-3">面談予約</h2>

            {/* Step 1: 先生を選ぶ */}
            <div className="card mb-3">
                <div className="card-header">1. 先生を選ぶ</div>
                <div className="card-body">
                    <label className="form-label">事務所</label>
                    <select
                        className="form-select"
                        value={tenantId}
                        onChange={(e) => setTenantId(Number(e.target.value) || "")}
                    >
                        <option value="">選択してください</option>
                        {tenants.map((t) => (
                            <option key={t.id} value={t.id}>
                                {t.name}
                            </option>
                        ))}
                    </select>

                    {tenantId && (
                        <>
                            <label className="form-label mt-3">担当者（先生）</label>
                            <select
                                className="form-select"
                                value={lawyerId}
                                onChange={(e) => setLawyerId(Number(e.target.value) || "")}
                            >
                                <option value="">選択してください</option>
                                {pros.map((p) => (
                                    <option key={p.user_id} value={p.user_id}>
                                        {p.name}
                                    </option>
                                ))}
                            </select>
                        </>
                    )}
                </div>
            </div>

            {/* Step 2: 空き枠から日時を選ぶ */}
            <div className="card mb-3">
                <div className="card-header">2. 日時を選ぶ</div>
                <div className="card-body">
                    {!tenantId && <div className="text-muted">先に先生を選択してください。</div>}
                    {tenantId && days.length === 0 && (
                        <div className="text-muted">読み込み中、または空き枠がありません。</div>
                    )}
                    {days.map((d) => (
                        <div key={d.date} className="mb-2">
                            <div className="fw-semibold mb-1">{d.date}</div>
                            <div className="d-flex flex-wrap gap-2">
                                {d.slots.map((s) => (
                                    <button
                                        key={s}
                                        type="button"
                                        className={
                                            "btn btn-sm " +
                                            (slot === s ? "btn-primary" : "btn-outline-secondary")
                                        }
                                        onClick={() => setSlot(s)}
                                    >
                                        {new Date(s).toLocaleTimeString([], {
                                            hour: "2-digit",
                                            minute: "2-digit",
                                        })}
                                    </button>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Step 3: お客様情報 */}
            <div className="card mb-3">
                <div className="card-header">3. お客様情報</div>
                <div className="card-body">
                    <div className="row g-2">
                        <div className="col-md-6">
                            <label className="form-label">お名前</label>
                            <input
                                className="form-control"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                            />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label">メール</label>
                            <input
                                className="form-control"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* 送信 */}
            <div className="d-flex gap-2">
                <button
                    className="btn btn-primary"
                    disabled={!canSubmit || submitting}
                    onClick={submit}
                >
                    {submitting ? "予約中…" : "予約を確定する"}
                </button>
            </div>

            {/* 結果表示 */}
            {result && (
                <div className="alert alert-success mt-3">
                    <div className="mb-1">
                        予約を受け付けました。開始時刻：
                        {new Date(result.starts_at!).toLocaleString()}
                    </div>
                    <div>
                        ゲスト用リンク：
                        <a href={result.guest} target="_blank" rel="noreferrer">
                            {result.guest}
                        </a>
                    </div>
                    <div>
                        ホスト用リンク：
                        <a href={result.host} target="_blank" rel="noreferrer">
                            {result.host}
                        </a>
                    </div>
                </div>
            )}
        </div>
    );
}
