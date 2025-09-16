// src/pages/Offices.tsx
import { useEffect, useState } from "react";
import API from "@/lib/api";

type Tenant = { id:number; slug:string; display_name:string; type:string; region:string; home_url?:string|null };

export default function Offices() {
    const [items, setItems] = useState<Tenant[]>([]);
    const [page, setPage] = useState(1);
    const [q, setQ] = useState("");
    const [region, setRegion] = useState("");
    const [type, setType] = useState("");
    const [meta, setMeta] = useState<any>(null);

    useEffect(() => {
        (async () => {
            const params = new URLSearchParams({ page: String(page), q, region, type });
            const res = await API.get(`/public/tenants/list?${params}`);
            setItems(res.data);
            setMeta(res.meta);
        })();
    }, [page, q, region, type]);

    return (
        <div className="mx-auto max-w-5xl p-6">
            <h1 className="text-2xl font-bold mb-4">事務所の一覧</h1>

            {/* 🔎 検索・絞り込み */}
            <div className="flex gap-2 mb-6">
                <input
                    type="text"
                    placeholder="事務所名で検索"
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    className="border rounded p-2 flex-1"
                />
                <select value={region} onChange={(e) => setRegion(e.target.value)} className="border rounded p-2">
                    <option value="">地域を選択</option>
                    <optgroup label="北海道・東北">
                        <option value="北海道">北海道</option>
                        <option value="青森">青森</option>
                        <option value="岩手">岩手</option>
                        <option value="宮城">宮城</option>
                        <option value="秋田">秋田</option>
                        <option value="山形">山形</option>
                        <option value="福島">福島</option>
                    </optgroup>

                    <optgroup label="関東">
                        <option value="茨城">茨城</option>
                        <option value="栃木">栃木</option>
                        <option value="群馬">群馬</option>
                        <option value="埼玉">埼玉</option>
                        <option value="千葉">千葉</option>
                        <option value="東京">東京</option>
                        <option value="神奈川">神奈川</option>
                    </optgroup>

                    <optgroup label="中部">
                        <option value="新潟">新潟</option>
                        <option value="富山">富山</option>
                        <option value="石川">石川</option>
                        <option value="福井">福井</option>
                        <option value="山梨">山梨</option>
                        <option value="長野">長野</option>
                        <option value="岐阜">岐阜</option>
                        <option value="静岡">静岡</option>
                        <option value="愛知">愛知</option>
                    </optgroup>

                    <optgroup label="近畿">
                        <option value="三重">三重</option>
                        <option value="滋賀">滋賀</option>
                        <option value="京都">京都</option>
                        <option value="大阪">大阪</option>
                        <option value="兵庫">兵庫</option>
                        <option value="奈良">奈良</option>
                        <option value="和歌山">和歌山</option>
                    </optgroup>

                    <optgroup label="中国">
                        <option value="鳥取">鳥取</option>
                        <option value="島根">島根</option>
                        <option value="岡山">岡山</option>
                        <option value="広島">広島</option>
                        <option value="山口">山口</option>
                    </optgroup>

                    <optgroup label="四国">
                        <option value="徳島">徳島</option>
                        <option value="香川">香川</option>
                        <option value="愛媛">愛媛</option>
                        <option value="高知">高知</option>
                    </optgroup>

                    <optgroup label="九州・沖縄">
                        <option value="福岡">福岡</option>
                        <option value="佐賀">佐賀</option>
                        <option value="長崎">長崎</option>
                        <option value="熊本">熊本</option>
                        <option value="大分">大分</option>
                        <option value="宮崎">宮崎</option>
                        <option value="鹿児島">鹿児島</option>
                        <option value="沖縄">沖縄</option>
                    </optgroup>
                </select>
                <select value={type} onChange={(e) => setType(e.target.value)} className="border rounded p-2">
                    <option value="">士業種別</option>
                    <optgroup label="法律系">
                        <option value="司法書士">司法書士</option>
                        <option value="行政書士">行政書士</option>
                        <option value="弁護士">弁護士</option>
                        <option value="弁理士">弁理士</option>
                        <option value="海事代理士">海事代理士</option>
                    </optgroup>

                    <optgroup label="会計・税務系">
                        <option value="税理士">税理士</option>
                        <option value="公認会計士">公認会計士</option>
                        <option value="中小企業診断士">中小企業診断士</option>
                    </optgroup>

                    <optgroup label="労務・社会保険系">
                        <option value="社会保険労務士">社会保険労務士</option>
                    </optgroup>

                    <optgroup label="不動産系">
                        <option value="土地家屋調査士">土地家屋調査士</option>
                        <option value="不動産鑑定士">不動産鑑定士</option>
                    </optgroup>

                    <optgroup label="技術系">
                        <option value="技術士">技術士</option>
                        <option value="建築士">建築士</option>
                    </optgroup>

                    <optgroup label="その他">
                        <option value="通関士">通関士</option>
                        <option value="気象予報士">気象予報士</option>
                        <option value="旅行業務取扱管理士">旅行業務取扱管理士</option>
                        <option value="販売士">販売士</option>
                    </optgroup>
                </select>
            </div>

            {/* 📋 一覧 */}
            <div className="grid sm:grid-cols-2 gap-4">
                {items.map((t) => (
                    <div key={t.id} className="border rounded-xl p-4">
                        <div className="font-semibold">{t.display_name}</div>
                        <div className="text-sm text-slate-500 mb-3">{t.region}・{t.type} /s/{t.slug}</div>
                        <div className="flex gap-2">
                            {t.home_url
                                ? <a className="btn btn-light" href={t.home_url!} target="_blank" rel="noreferrer">事務所のHP</a>
                                : <a className="btn btn-light" href={`/s/${t.slug}`}>事務所ページ</a>}
                            <a className="btn btn-primary" href={`/s/${t.slug}/reserve`}>予約する</a>
                        </div>
                    </div>
                ))}
            </div>

            {/* ◀ ページネーション ▶ */}
            {meta && (
                <div className="flex justify-center gap-2 mt-6">
                    {Array.from({ length: meta.total_pages }, (_, i) => i + 1).map((p) => (
                        <button
                            key={p}
                            onClick={() => setPage(p)}
                            className={`px-3 py-1 rounded ${p === page ? "bg-indigo-600 text-white" : "bg-slate-100"}`}
                        >
                            {p}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
