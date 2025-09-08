import React from "react";
import type { FeaturesData } from "./types";

export default function Features({ data }: { data: FeaturesData }) {
    const items = data.items ?? [];
    return (
        <section className="py-2">
            <div className="row g-3">
                {items.map((it, i) => (
                    <div className="col-md-4" key={i}>
                        <div className="border rounded-4 p-4 h-100">
                            <div className="fw-semibold">{it.title ?? "特徴"}</div>
                            {it.text && <p className="text-muted mt-2 mb-0">{it.text}</p>}
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}
