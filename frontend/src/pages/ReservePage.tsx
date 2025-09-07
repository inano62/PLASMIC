// src/pages/ReservePage.tsx
import { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import BookingForm from "../components/BookingForm";

type DayGroup = { date: string; slots: string[] };

export default function ReservePage() {
    const { tenant = "" } = useParams<{ tenant: string }>();
    const [days, setDays] = useState<DayGroup[]>([]);
    const [prefill, setPrefill] = useState<string | undefined>(
        new URLSearchParams(location.search).get("prefill") ?? undefined
    );

    useEffect(() => {
        const from = new Date().toISOString();
        fetch(`/api/tenants/${tenant}/availability?from=${encodeURIComponent(from)}&days=7&duration=30`)
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(j => setDays(j.days || []))
            .catch(console.error);
    }, [tenant]);


    if (!tenant) return null; // パラメータ未取得の瞬間対策

    return (
        <div style={{ padding: 16 }}>
            <h2>面談予約</h2>

            {/* ★ tenant を必ず渡す */}
            <BookingForm tenant={tenant} lawyerId={1} defaultStartsAt={prefill} />

            <h3 style={{ marginTop: 16 }}>空き枠（30分刻み・1週間）</h3>
            <div
                style={{
                    display: "grid",
                    gridTemplateColumns: "repeat(auto-fit,minmax(220px,1fr))",
                    gap: 12,
                }}
            >
                {days.map((d) => (
                    <div
                        key={d.date}
                        style={{ border: "1px solid #eee", borderRadius: 12, padding: 12 }}
                    >
                        <strong>
                            {new Date(d.date).toLocaleDateString("ja-JP", {
                                weekday: "short",
                                month: "numeric",
                                day: "numeric",
                            })}
                        </strong>
                        <div style={{ display: "flex", flexWrap: "wrap", gap: 8, marginTop: 8 }}>
                            {d.slots.map((iso) => (
                                <button
                                    key={iso}
                                    type="button"
                                    onClick={() => setPrefill(iso)}
                                    style={{ padding: "6px 10px", borderRadius: 10, border: "1px solid #ddd" }}
                                >
                                    {new Date(iso).toLocaleTimeString("ja-JP", {
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
    );
}
