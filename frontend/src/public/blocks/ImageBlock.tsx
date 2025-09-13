export default function ImageBlock({ data }:{
    data: { imgUrl?: string; alt?: string; caption?: string; width?: string; align?: string; radius?: string; shadow?: boolean; linkUrl?: string }
}) {
    if (!data?.imgUrl) return null;

    const radius = data.radius === "xl" ? "rounded-2xl" : data.radius === "md" ? "rounded-md" : "";
    const shadow = data.shadow ? "shadow-md" : "";
    const width  = data.width === "half" ? "max-w-xl" : data.width === "content" ? "max-w-3xl" : "w-full";
    const align  = data.align === "left" ? "mx-0" : data.align === "right" ? "ml-auto" : "mx-auto";

    const img = (
        <img
            src={data.imgUrl}
            alt={data.alt || ""}
            loading="lazy"
            className={`${width} ${align} ${radius} ${shadow} block object-cover`}
        />
    );

    return (
        <figure className="my-6">
            {data.linkUrl ? <a href={data.linkUrl}>{img}</a> : img}
            {data.caption && (
                <figcaption className="text-center text-sm text-slate-500 mt-2">
                    {data.caption}
                </figcaption>
            )}
        </figure>
    );
}
