
type MediaData = {
    imgUrl?: string;
    caption?: string;
    align?: "center" | "left" | "right";
    width?: number; // px
    shadow?: boolean;
    rounded?: boolean;
};

export default function MediaCard({ data }: { data: MediaData }) {
    const { imgUrl, caption, align = "center", width = 680, shadow = true, rounded = true } = data || {};
    if (!imgUrl) return null;

    const alignClass =
        align === "left" ? "md:mr-auto md:ml-0" :
            align === "right" ? "md:ml-auto md:mr-0" : "mx-auto";

    return (
        <figure className={`my-6 ${alignClass}`} style={{ maxWidth: width }}>
            <img
                src={imgUrl}
                alt={caption ?? ""}
                className={[
                    "w-full h-auto",
                    shadow ? "shadow-lg" : "",
                    rounded ? "rounded-2xl" : "",
                    "border border-slate-200"
                ].join(" ")}
                loading="lazy"
            />
            {caption && <figcaption className="text-sm text-slate-500 mt-2">{caption}</figcaption>}
        </figure>
    );
}
