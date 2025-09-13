// // Reserve.tsx（抜粋）
// import { useEffect, useState } from "react";
// import { useSearchParams } from "react-router-dom";
// import { API } from "@/lib/api"; // あなたのAPIラッパ
//
// type Office = { id:number; name:string };
// type Site = { id:number; slug:string; title:string; office?: Office };
//
// export default function Reserve() {
//     const [params] = useSearchParams();
//     const [offices, setOffices] = useState<Office[]>([]);
//     const [selected, setSelected] = useState<Office | null>(null);
//     const [loading, setLoading] = useState(true);
//
//     useEffect(() => {
//         (async () => {
//             setLoading(true);
//
//             // ① officeId 直指定なら最速
//             const officeId = params.get("officeId");
//             if (officeId) {
//                 const o = await API.getJSON<Office>(`/offices/${officeId}`); // 既存のエンドポイントに合わせて
//                 setSelected(o);
//                 setLoading(false);
//                 return;
//             }
//
//             // ② slug から先生解決
//             const slug = params.get("slug");
//             if (slug) {
//                 const site = await API.getJSON<Site>(`/public/sites/by-slug/${encodeURIComponent(slug)}`);
//                 if (site?.office) setSelected(site.office);
//                 setLoading(false);
//                 return;
//             }
//
//             // ③ 従来どおり一覧から選ばせる
//             const list = await API.getJSON<Office[]>(`/offices`);
//             setOffices(list);
//             setLoading(false);
//         })();
//     }, [params]);
//
//     if (loading) return <div>読み込み中…</div>;
//
//     const fixedByContext = !!selected; // slug/officeId で決まっている
//
//     return (
//         <div>
//             <h1>面談予約</h1>
//
//             {/* Step 1: 先生を選ぶ（固定時は表示を簡略化 or 非表示） */}
//             {fixedByContext ? (
//                 <div className="mb-3">
//                     <label className="form-label">1. 先生</label>
//                     <div className="form-control-plaintext">{selected!.name}</div>
//                     {/* 送信用に hidden */}
//                     <input type="hidden" name="office_id" value={selected!.id} />
//                 </div>
//             ) : (
//                 <div className="mb-3">
//                     <label className="form-label">1. 先生を選ぶ</label>
//                     <select
//                         className="form-select"
//                         value={selected?.id ?? ""}
//                         onChange={(e) => {
//                             const id = Number(e.target.value);
//                             const o = offices.find(x => x.id === id) ?? null;
//                             setSelected(o);
//                         }}
//                     >
//                         <option value="">選択してください</option>
//                         {offices.map(o => (
//                             <option key={o.id} value={o.id}>{o.name}</option>
//                         ))}
//                     </select>
//                 </div>
//             )}
//
//             {/* Step 2 以降は今まで通り。submit 時は selected!.id を office_id に使う */}
//             {/* …日時選択…お客様情報… */}
//         </div>
//     );
// }
