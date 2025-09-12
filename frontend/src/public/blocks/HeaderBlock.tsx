import React from "react";

export default function HeaderBlock({ data }: { data: { imgUrl?: string, altText?: string } }) {
    if (!data?.imgUrl) return null;
    return (
        <div className="relative w-full h-40 md:h-60">
            <img
                src={data.imgUrl}
                alt={data.altText ?? "ヘッダー画像"}
                className="w-full h-full object-cover"
            />
            <div className="absolute inset-0 bg-black/20" /> {/* オーバーレイ */}
        </div>
    );
}
