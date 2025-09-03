import {useNavigate} from "react-router-dom";
import {useSiteSettings} from "../../lib/siteSettings";
import React, {useEffect, useMemo, useState} from "react";
import IntakePanel from "../../components/admin/IntakePanel";

type Appt = { id: number; client_name: string; starts_at: string; room: string };

const ADMIN_TOKEN_KEY = "admin.token";

export default function AdminDashboard() {
    const [rows, setRows] = useState<Appt[]>([]);

    useEffect(() => {
        fetch("/api/appointments/nearby?lawyer_id=1")
            .then((r) => r.ok ? r.json() : Promise.reject(r))
            .then(setRows)
            .catch((e) => console.error("fetch appointments failed", e));
    }, []);

    function canStart(iso: string) {
        const start = new Date(iso).getTime();
        const now = Date.now();
        const fiveMinBefore = start - 5 * 60 * 1000;
        const sixtyMinAfter = start + 60 * 60 * 1000;
        return now >= fiveMinBefore && now <= sixtyMinAfter;
    }

    // 今週の 9:00〜17:30 / 30分グリッド
    const grid = useMemo(() => {
        const now = new Date();
        const monday = new Date(now);
        const d = (now.getDay() + 6) % 7; // 月曜始まり
        monday.setDate(now.getDate() - d);
        const days = [...Array(5)].map((_, i) => new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + i));
        const slots = [...Array(17)].map((_, i) => i); // 9:00〜17:30
        return {days, slots};
    }, []);

    function match(ap: Appt, day: Date, slotIndex: number) {
        const t = new Date(ap.starts_at);
        return (
            t.getFullYear() === day.getFullYear() &&
            t.getMonth() === day.getMonth() &&
            t.getDate() === day.getDate() &&
            t.getHours() === 9 + Math.floor(slotIndex / 2) &&
            t.getMinutes() === (slotIndex % 2) * 30
        );
    }

    const nav = useNavigate();

    // 認証ガード（描画中に nav しない）
    useEffect(() => {
        if (!localStorage.getItem(ADMIN_TOKEN_KEY)) {
            nav("/admin", {replace: true});
        }
    }, [nav]);

    const s = useSiteSettings();
    const [draft, setDraft] = useState({...s});

    function onChange<K extends keyof typeof draft>(k: K, v: (typeof draft)[K]) {
        setDraft((prev) => ({...prev, [k]: v}));
    }

    async function save() {
        s.overwrite?.(draft); // ローカル保存（フック側にあれば）
        try {
            // 将来: Laravel APIへ永続化
            // await fetch('/api/admin/site-settings', { method:'PUT', headers:{ 'Content-Type':'application/json', 'Authorization':`Bearer ${localStorage.getItem(ADMIN_TOKEN_KEY)}` }, body: JSON.stringify(draft) });
            alert("保存しました");
        } catch (e) {
            alert("サーバ保存に失敗しました");
        }
    }

    return (
        <div>
            <div className="mt-8 grid md:grid-cols-2 gap-8" >
                {/* 予約パネル */}
                <div className="min-w-0">
                    <div className="rounded-2xl border bg-white p-6 shadow-sm min-w-0 overflow-x-hidden">
                        <h2 className="font-semibold mb-3">今週の予約</h2>

                        {/* 近日のリスト */}
                        <ul className="list-none p-0 mb-4">
                            {rows.map((r) => (
                                <li key={r.id} className="flex items-center gap-3 py-1">
                                    <div className="time w-16 tabular-nums">
                                        {new Date(r.starts_at).toLocaleTimeString([], {
                                            hour: "2-digit",
                                            minute: "2-digit"
                                        })}
                                    </div>
                                    <div className="name flex-1">{r.client_name}</div>

                                    {canStart(r.starts_at) ? (
                                        <a href={`/host?aid=${r.id}`} className="btn btn-sm btn-primary no-underline">
                                            開始
                                        </a>
                                    ) : (
                                        <a href={`/admin/appointments/${r.id}`}
                                           className="btn btn-sm btn-outline-secondary no-underline">
                                            詳細
                                        </a>
                                    )}

                                    {new Date(r.starts_at).getTime() < Date.now() && !canStart(r.starts_at) && (
                                        <span className="text-gray-400 text-sm">終了</span>
                                    )}
                                </li>
                            ))}
                            {rows.length === 0 && <li className="text-sm text-gray-500">直近の予約はありません</li>}
                        </ul>

                        {/* 週次グリッド */}
                        <div style={{
                            display: "grid",
                            gridTemplateColumns: "120px repeat(5,1fr)",
                            borderTop: "1px solid #eee"
                        }}>
                            <div></div>
                            {grid.days.map((d) => (
                                <div key={d.toDateString()} style={{padding: 8, fontWeight: 700}}>
                                    {d.toLocaleDateString("ja-JP", {
                                        weekday: "short",
                                        month: "numeric",
                                        day: "numeric"
                                    })}
                                </div>
                            ))}

                            {grid.slots.map((i) => (
                                <React.Fragment key={i}>
                                    <div style={{padding: 8, borderTop: "1px solid #f2f2f2"}}>
                                        {`${String(9 + Math.floor(i / 2)).padStart(2, "0")}:${i % 2 ? "30" : "00"}`}
                                    </div>
                                    {grid.days.map((day, di) => (
                                        <div key={`c-${di}-${i}`}
                                             style={{padding: 6, borderTop: "1px solid #f2f2f2", minHeight: 36}}>
                                            {rows
                                                .filter((a) => match(a, day, i))
                                                .map((a) => (
                                                    <a
                                                        key={a.id}
                                                        href={`/host?aid=${a.id}`}
                                                        style={{
                                                            display: "inline-block",
                                                            padding: "4px 8px",
                                                            borderRadius: 8,
                                                            background: "#ece9ff",
                                                            textDecoration: "none",
                                                        }}
                                                    >
                                                        {a.client_name}
                                                    </a>
                                                ))}
                                        </div>
                                    ))}
                                </React.Fragment>
                            ))}
                        </div>
                    </div>
                </div>
                {/* サイト設定（例） */}
                <div className="rounded-2xl border bg-white p-6 shadow-sm min-w-0 overflow-x-hidden">
                    <h2 className="fw-semibold mb-3">問い合わせ / トリアージ</h2>
                    <IntakePanel onApprove={(inq) => {/* 承認時に予約作成や通知など */
                    }}/>
                </div>
            </div>

            <div className="mt-8 flex items-center gap-3">
                <button onClick={save} className="rounded-xl px-6 py-3 bg-indigo-600 text-white hover:bg-indigo-500">
                    保存
                </button>
                <button onClick={() => setDraft({...s})} className="rounded-xl px-6 py-3 border">
                    破棄
                </button>
            </div>
        </div>
    );
}
