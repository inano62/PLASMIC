// src/public/blocks/parts/Features.tsx
export default function Features({ d }: { d: any }){
    const items = (d?.items ?? []) as {title?:string; text?:string}[];
    return (
        <section className="features">
            <div className="grid">
                {items.map((it,i)=> (
                    <div key={i} className="card">
                        <div className="card-title">{it.title ?? '特徴'}</div>
                        <p className="card-text">{it.text ?? ''}</p>
                    </div>
                ))}
            </div>
        </section>
    );
}