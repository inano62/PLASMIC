import { useEffect, useState } from "react";

export default function Success() {
    const [data, setData] = useState<any>(null);
    const id = new URLSearchParams(location.search).get("id")!;

    useEffect(() => {
        (async () => {
            const r = await fetch(`/api/reservations/${id}`);
            if (r.status === 200) setData(await r.json());
            else setData({ error: "payment_required" });
        })();
    }, [id]);

    if (!data) return <p>読み込み中...</p>;
    if (data.error) return <p>まだ決済が反映されていません。数秒後に再読み込みしてください。</p>;

    return (
        <div className="p-4">
            <p>ホストURL: <a href={data.host_url}>{data.host_url}</a></p>
            <p>ゲストURL: <a href={data.guest_url}>{data.guest_url}</a></p>
        </div>
    );
}
