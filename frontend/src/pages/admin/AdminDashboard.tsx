// src/pages/admin/Dashboard.tsx
import { useNavigate } from "react-router-dom";
import  {useEffect, useRef, useState} from "react";

type ApptRow = { id: number; client_name: string; starts_at: string; room?: string };

// 右パネルで使う詳細
type ApptDetail = {
    id: number;
    client_name: string;
    client_email?: string;
    starts_at: string;           // ISO
    hostJoinPath?: string;
    clientJoinPath?: string;
};

const ADMIN_TOKEN_KEY = "admin.token";

export default function AdminDashboard() {
    const [rows, setRows] = useState<ApptRow[]>([]);
    const [selected, setSelected] = useState<ApptDetail | null>(null);
    const [detailError, setDetailError] = useState<string|null>(null);
    const [loadingDetail, setLoadingDetail] = useState(false);
    const detailAbortRef = useRef<AbortController|null>(null);

    function canStart(iso: string) {
        const start = new Date(iso).getTime();
        const now = Date.now();
        const fiveMinBefore = start - 5 * 60 * 1000;
        const sixtyMinAfter = start + 60 * 60 * 1000;
        return now >= fiveMinBefore && now <= sixtyMinAfter;
    }

    // 今週の 9:00〜17:30 / 30分グリッド（今は未表示のまま維持）
    // const grid = useMemo(() => {
    //     const now = new Date();
    //     const monday = new Date(now);
    //     const d = (now.getDay() + 6) % 7;
    //     monday.setDate(now.getDate() - d);
    //     const days = [...Array(5)].map((_, i) => new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + i));
    //     const slots = [...Array(17)].map((_, i) => i);
    //     return { days, slots };
    // }, []);

    const nav = useNavigate();

    // 認証ガード
    useEffect(() => {
        if (!localStorage.getItem(ADMIN_TOKEN_KEY)) {
            nav("/admin", { replace: true });
        }
    }, [nav]);

    async function openDetail(id: number) {
        setLoadingDetail(true);
        detailAbortRef.current?.abort();
        const ac = new AbortController();
        detailAbortRef.current = ac;
        setLoadingDetail(true);
        setDetailError(null);
        setSelected(null);
        // 取得中でも右側に最低限の情報を出す（一覧からのフォールバック）
        const base = rows.find(r => r.id === id);
        if (base) {
            setSelected({
                id: base.id,
                client_name: base.client_name,
                starts_at: base.starts_at,
            });
        }

        try {
            const r = await fetch(`/api/appointments/${id}`, { signal: ac.signal });
            // ★ 詳細APIを叩いて右ペインに反映
            if (!r.ok) throw new Error(`HTTP ${r.status}`);

            const ap: ApptDetail = await r.json();
            setSelected(ap);
        } catch (e) {
            if (e?.name === "AbortError") return; // 途中キャンセルは無視
            console.error("openDetail failed:", e);
            setDetailError("詳細の取得に失敗しました。時間をおいて再度お試しください。");
        } finally {
            setLoadingDetail(false);
        }
    }

    function buildLinks(ap: ApptDetail) {
        const host = ap.hostJoinPath ?? `/host?aid=${ap.id}`;
        const guest = ap.clientJoinPath ?? `/wait?aid=${ap.id}`;
        return { host, guest };
    }

    function mailInvite(ap: ApptDetail) {
        if (!ap.client_email) return;
        const { guest } = buildLinks(ap);
        const subj = encodeURIComponent("面談のご案内");
        const body = encodeURIComponent(
            `以下のリンクからご入室ください。\n${location.origin}${guest}\n\n開始時刻: ${new Date(ap.starts_at).toLocaleString()}`
        );
        location.href = `mailto:${ap.client_email}?subject=${subj}&body=${body}`;
    }
    return (
        <div>
            <div className="mt-8 grid md:grid-cols-2 gap-8">
                {/* 左：今週の予約 */}
                <div className="min-w-0">
                    <div className="rounded-2xl border bg-white p-6 shadow-sm min-w-0 overflow-x-hidden">
                        <h2 className="font-semibold mb-3">今週の予約</h2>

                        {/* 近日のリスト */}
                        <ul className="list-none p-0 mb-4">
                            {rows.map((r) => (
                                <li key={r.id} className="flex items-center gap-3 py-1">
                                    <div className="time w-16 tabular-nums">
                                        {new Date(r.starts_at).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })}
                                    </div>
                                    <div className="name flex-1">{r.client_name}</div>

                                    {canStart(r.starts_at) ? (
                                        <a href={`/host?aid=${r.id}`} className="btn btn-sm btn-primary no-underline">
                                            開始
                                        </a>
                                    ) : (
                                        <button
                                            className="btn btn-sm btn-outline-secondary"
                                            onClick={() => openDetail(r.id)}
                                        >
                                            詳細
                                        </button>
                                    )}

                                    {new Date(r.starts_at).getTime() < Date.now() && !canStart(r.starts_at) && (
                                        <span className="text-gray-400 text-sm">終了</span>
                                    )}
                                </li>
                            ))}
                            {rows.length === 0 && <li className="text-sm text-gray-500">直近の予約はありません</li>}
                        </ul>
                    </div>
                </div>

                {/* 右：詳細 or 問い合わせ/トリアージ */}
                <div className="rounded-2xl border bg-white p-6 shadow-sm min-w-0 overflow-x-hidden">
                    {!selected ? (
                        <>
                            <h2 className="fw-semibold mb-3">問い合わせ / トリアージ</h2>
                            {/*<IntakePanel />*/}
                        </>
                    ) : (
                        <>
                            <h2 className="fw-semibold mb-3">予約の詳細</h2>
                            {loadingDetail ? (
                                <div className="text-muted">読み込み中…</div>
                            ) : (
                                <>
                                    <div className="mb-3">
                                        <div className="text-muted text-sm">開始</div>
                                        <div className="fw-semibold">{new Date(selected.starts_at).toLocaleString()}</div>
                                    </div>

                                    <div className="mb-3">
                                        <div className="text-muted text-sm">お客様</div>
                                        <div>{selected.client_name}</div>
                                        {selected.client_email && <div className="text-muted small">{selected.client_email}</div>}
                                    </div>

                                    <div className="mb-3">
                                        <div className="text-muted text-sm">入室リンク</div>
                                        {(() => {
                                            const { host, guest } = buildLinks(selected);
                                            return (
                                                <ul className="list-unstyled">
                                                    <li>ゲスト: <a href={guest} target="_blank" rel="noreferrer">{location.origin + guest}</a></li>
                                                    <li>ホスト: <a href={host} target="_blank" rel="noreferrer">{location.origin + host}</a></li>
                                                </ul>
                                            );
                                        })()}
                                    </div>

                                    <div className="d-flex gap-2">
                                        <button
                                            className="btn btn-primary"
                                            onClick={() => mailInvite(selected)}
                                            disabled={!selected.client_email}
                                            title={selected.client_email ? "" : "メールアドレスが未登録です"}
                                        >
                                            メールで案内を送る
                                        </button>
                                        <a
                                            className="btn btn-outline-secondary"
                                            href={buildLinks(selected).host}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            今すぐビデオ
                                        </a>
                                        <button className="btn btn-light" onClick={() => setSelected(null)}>閉じる</button>
                                    </div>
                                </>
                            )}
                        </>
                    )}
                </div>
            </div>

        </div>
    );
}
