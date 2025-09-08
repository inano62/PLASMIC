import React from "react";
import type { CtaData } from "./types";

export default function Cta({ data }: { data: CtaData }) {
    return (
        <section className="text-center py-5 bg-light rounded-4">
            <div className="fs-4">{data.text ?? "お問い合わせ"}</div>
            <a href={data.btnHref ?? "#"} className="btn btn-outline-dark rounded-pill px-4 mt-3">
                {data.btnText ?? "送信"}
            </a>
        </section>
    );
}
