import { PRICING } from "../model/copy";

export function Pricing({ signupHref }:{ signupHref: string }) {
    return (
        <section id="pricing" className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
            <h2 className="text-2xl font-bold">料金（ワンプラン）</h2>
            <p className="text-slate-600 mt-2">HP＋管理画面＋ビデオチャット＋決済。隠れ費用はありません。</p>
            <div className="mt-8 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                {PRICING.map(p => (
                    <div key={p.name} className="rounded-2xl border border-slate-200 p-6 bg-white shadow-sm flex flex-col">
                        <div className="text-sm font-semibold">{p.name}</div>
                        <div className="mt-4 text-3xl font-extrabold">{p.price}</div>
                        <ul className="mt-4 space-y-2 text-sm text-slate-600">
                            {p.points.map(pt => <li key={pt}>• {pt}</li>)}
                        </ul>
                        <a href={signupHref} className="mt-6 px-4 py-2 rounded-xl bg-indigo-600 text-white text-center hover:bg-indigo-700">申し込む</a>
                    </div>
                ))}
            </div>
            <p className="text-xs text-slate-500 mt-4">※ カスタム要件がある場合はご相談ください。</p>
        </section>
    );
}
