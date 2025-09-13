import React from "react";
import type { HeroData } from "./types";

export default function Hero({ data }: { data: HeroData }) {
    const kicker   = data?.kicker ?? "";
    const title    = data?.headline ?? data?.title ?? "";
    const subtitle = data?.subtitle ?? "";
    const btnText  = data?.btnText ?? "";
    const btnHref  = data?.btnHref ?? "#";
    const bg = data?.bgUrl ?? data?.imageUrl ?? data?.imgUrl;      // 後方互換
    const av = data?.avatarUrl;
    console.log('bg =', bg);
    console.log('av =', av);
    return (
        <div className="relative">
            <section className="position-relative text-center text-white"
                     style={bg ? { backgroundImage:`url(${bg})`, backgroundSize:"cover", backgroundPosition:"center" } : {}}>

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
            {/*{imgUrl && (*/}
            {/*    <div className="mt-8">*/}
            {/*        <img src={imgUrl} alt="" className="img-fluid rounded-2xl border border-slate-200 mt-4" />*/}
            {/*    </div>*/}
            {/*)}*/}
            {av && (
                <img src={av} className="rounded-circle mb-3"
                     style={{width:120, height:120, objectFit:"cover", border:"4px solid #fff"}} alt=""/>
            )}
        </section>
        </div>
    );
}
