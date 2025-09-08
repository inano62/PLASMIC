import React from "react";
import type { HeroData } from "./types";

export default function Hero({ data }: { data: HeroData }) {
    return (
        <section className="text-center py-5">
            {data.kicker && <div className="text-primary small">{data.kicker}</div>}
            <h1 className="fw-bold display-6 mt-2">{data.title ?? "タイトル"}</h1>
            {data.subtitle && <p className="text-muted fs-5 mt-2">{data.subtitle}</p>}
            {data.btnText && (
                <a href={data.btnHref ?? "#"} className="btn btn-dark rounded-pill px-4 mt-3">
                    {data.btnText}
                </a>
            )}
        </section>
    );
}
