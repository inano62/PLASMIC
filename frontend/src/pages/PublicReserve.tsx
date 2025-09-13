import { useEffect, useMemo, useState } from "react";
import API from "../lib/api.ts";

// ✅ ここを絶対URLに。env があればそれを優先
const API_BASE = import.meta.env.VITE_API_BASE ?? "http://localhost:8000/api";
const buildUrl = (path: string) => {
    const p = path.startsWith("/") ? path.slice(1) : path; // 先頭スラッシュを除去
    return `${API_BASE}/${p}`;
};

type Tenant = { id: number; display_name: string };
type Pro    = { user_id: number; name: string };
type SlotDay = { date: string; slots: string[] };

// ---------- 共通 fetch ----------
async function jget<T>(path: string): Promise<T> {
    const r = await fetch(buildUrl(path), {
        method: "GET",
        headers: { Accept: "application/json" },
    });
    let data: any = null;
    try { data = await r.json(); } catch {}
    if (!r.ok) throw new Error(data?.message || data?.error || r.statusText);
    return data as T;
}

async function jpost<T>(path: string, body: any): Promise<T> {
    const r = await fetch(buildUrl(path), {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify(body),
    });
    let data: any = null;
    try { data = await r.json(); } catch {}
    if (!r.ok) throw new Error(data?.message || data?.error || r.statusText);
    return data as T;
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
    const [result, setResult] = useState<{ host: string; guest: string; starts_at?: string } | null>(null);

    // --- 初期化：?tenant / ?tenant_id / ?slug に対応 ---
    useEffect(() => {
        (async () => {
            const qs = new URLSearchParams(location.search);
            const tParam  = qs.get("tenant") ?? qs.get("tenant_id");
            const slugParam = qs.get("slug"); // 先生サイトから ?slug=demo で来る

            if (tParam) {
                if (/^\d+$/.test(tParam)) {
                    setTenantId(Number(tParam));
                } else {
                    // ✅ tenant の slug のときは resolveTenant を使う（JSON）
                    const r = await API.get<{ id: number; name: string }>(
                        `public/tenants/resolve?slug=${encodeURIComponent(tParam)}`
                    );
                    setTenantId(r.id);
                }
                return;
            }

            if (slugParam) {

                const site: any = await API.get(
                    `public/sites/${encodeURIComponent(slugParam)}`
                );
                const tid =
                    site?.tenant?.id ??
                    site?.tenant_id ??
                    site?.office?.tenant_id;
                if (tid) setTenantId(Number(tid));
            }
        })().catch((e) => console.error(e));
    }, []);

    // booleanに正規化
    const canSubmit = useMemo(
        () => !!tenantId && !!lawyerId && !!slot && !!name && /\S+@\S+/.test(email),
        [tenantId, lawyerId, slot, name, email]
    );

    // 先生一覧（事務所）
    useEffect(() => {
        jget<Tenant[]>("public/tenants").then(setTenants).catch(() => setTenants([]));
    }, []);

    // 所属の先生一覧
    useEffect(() => {
        if (!tenantId) {
            setPros([]); setLawyerId(""); return;
        }
        jget<Pro[]>(`public/tenants/${tenantId}/pros`)
            .then(setPros)
            .catch(() => { setPros([]); setLawyerId(""); });
    }, [tenantId]);

    // 空き枠
    useEffect(() => {
        if (!tenantId || !lawyerId) {
            setDays([]); setSlot(""); return;
        }
        jget<SlotDay[]>(`public/tenants/${tenantId}/slots?lawyer_id=${lawyerId}`)
            .then(setDays)
            .catch(() => setDays([]));
    }, [tenantId, lawyerId]);

    // 送信
    async function submit() {
        try {
            if (!tenantId || !lawyerId || !slot) {
                alert("入力が不足しています"); return;
            }
            setSubmitting(true);

            // 1) 顧客 upsert
            const cu = await jpost<{ user_id: number; magic_url?: string }>(
                "clients/upsert", { name, email }
            );

            // 2) 予約作成
            const payload = {
                lawyer_id: Number(lawyerId),
                client_name: name,
                client_email: email,
                // サーバの期待に合わせて両対応（どちらか片方だけ使われる想定）
                start_at: new Date(slot).toISOString(),
                starts_at: new Date(slot).toISOString(),
                visitor_id: String(cu.user_id ?? "public"),
                purpose_title: "オンライン相談",
                purpose_detail: "",
            };

            // まずはテナント版
            let ap = await jpost<{ clientJoinPath: string; hostJoinPath: string }>(
                `tenants/${String(tenantId)}/appointments`, payload
            );

            setResult({ host: ap.hostJoinPath, guest: ap.clientJoinPath, starts_at: slot });
            alert("予約が確定しました");

        } catch (e: any) {
            // サーバからのエラー文言をそのまま表示
            const msg = e?.message || String(e);
            alert(`予約に失敗しました\n${msg}`);
            console.error(e);
        } finally {
            setSubmitting(false);
        }
    }

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
                            <option key={t.id} value={t.id}>{t.display_name}</option>
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
                                    <option key={p.user_id} value={p.user_id}>{p.name}</option>
                                ))}
                            </select>
                        </>
                    )}
                </div>
            </div>

            {/* Step 2 */}
            <div className="card mb-3">
                <div className="card-header">2. 日時を選ぶ</div>
                <div className="card-body">
                    {!tenantId && <div className="text-muted">先に先生を選択してください。</div>}
                    {tenantId && days.length === 0 && <div className="text-muted">読み込み中、または空き枠がありません。</div>}
                    {days.map((d) => (
                        <div key={d.date} className="mb-2">
                            <div className="fw-semibold mb-1">{d.date}</div>
                            <div className="d-flex flex-wrap gap-2">
                                {d.slots.map((s) => (
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

            {/* Step 3 */}
            <div className="card mb-3">
                <div className="card-header">3. お客様情報</div>
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
                </div>
            </div>

            {/* 送信 */}
            <div className="d-flex gap-2">
                <button className="btn btn-primary" disabled={!canSubmit || submitting} onClick={submit}>
                    {submitting ? "予約中…" : "予約を確定する"}
                </button>
            </div>

            {/* 結果 */}
            {result && (
                <div className="alert alert-success mt-3">
                    <div className="mb-1">
                        予約を受け付けました。開始時刻：{new Date(result.starts_at!).toLocaleString()}
                    </div>
                    <div>
                        ゲスト用リンク：<a href={result.guest} target="_blank" rel="noreferrer">{result.guest}</a>
                    </div>
                    <div>
                        ホスト用リンク：<a href={result.host} target="_blank" rel="noreferrer">{result.host}</a>
                    </div>
                </div>
            )}
        </div>
    );
}
