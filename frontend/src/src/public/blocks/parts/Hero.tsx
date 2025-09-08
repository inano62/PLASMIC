// src/public/blocks/parts/Hero.tsx
export default function Hero({ d }: { d: any }){
    return (
        <section className="hero">
            {d?.kicker && <div className="hero-kicker">{d.kicker}</div>}
            <h1 className="hero-title">{d?.title ?? 'タイトル'}</h1>
            {d?.subtitle && <p className="hero-sub">{d.subtitle}</p>}
            {d?.btnText && (
                <a className="btn primary" href={d?.btnHref ?? '#'}>{d.btnText}</a>
            )}
        </section>
    );
}