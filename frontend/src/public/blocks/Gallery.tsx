export default function Gallery({ data }:{
    data:{ items:{imgUrl:string;alt?:string}[]; columns?: any; gap?: number; radius?: string }
}) {
    const cols = data.columns ?? { sm:2, md:3, lg:4 };
    const r = data.radius === "xl" ? "rounded-2xl" : data.radius === "md" ? "rounded-md" : "";
    const gap = data.gap ?? 12;

    return (
        <div
            className={`grid`}
            style={{
                gap,
                gridTemplateColumns: `repeat(${cols.sm}, 1fr)`
            }}
        >
            <style>{`
        @media (min-width: 768px){ .gallery-md { grid-template-columns: repeat(${cols.md}, 1fr); } }
        @media (min-width: 1024px){ .gallery-lg { grid-template-columns: repeat(${cols.lg}, 1fr); } }
      `}</style>
            <div className="contents gallery-md gallery-lg">
                {data.items?.map((it, i) => (
                    <img key={i} src={it.imgUrl} alt={it.alt||""} loading="lazy" className={`${r} w-full h-full object-cover`} />
                ))}
            </div>
        </div>
    );
}
