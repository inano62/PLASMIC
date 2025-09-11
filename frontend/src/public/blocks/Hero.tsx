import React from "react";
import type { HeroData } from "./types";

export default function Hero({ data }: { data: HeroData }) {
    const kicker   = data?.kicker ?? "";
    const title    = data?.headline ?? data?.title ?? "";
    const subtitle = data?.subtitle ?? "";
    const btnText  = data?.btnText ?? "";
    const btnHref  = data?.btnHref ?? "#";
    const imgUrl   = data?.imgUrl ?? null;
    return (
        <section className="text-center py-5">
            {kicker && <div className="text-sm text-slate-500">{kicker}</div>}
            {title && <h1 className="text-4xl font-extrabold my-3">{title}</h1>}
            {subtitle && <p className="text-slate-600">{subtitle}</p>}

            {/* CTA */}
            {btnText && (
                <div className="mt-4">
                    <a href={btnHref} className="px-5 py-3 rounded-2xl bg-indigo-600 text-white hover:bg-indigo-700">
                        {btnText}
                    </a>
                </div>
            )}

            {/* 画像（任意） */}
            {imgUrl && (
                <div className="mt-8">
                    <img src={imgUrl} alt="" className="max-w-full rounded-2xl border border-slate-200" />
                </div>
            )}
        </section>
    );
}
