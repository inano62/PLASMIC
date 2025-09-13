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
            <div className="container">
                {/* 画像があれば表示 */}
                {d.imageUrl && (
                    <div className="mb-3">
                        <img src={d.imageUrl} alt="" style={{maxWidth:"100%", height:"auto"}} />
                    </div>
                )}
                <small className="text-muted d-block">{d.kicker}</small>
                <h1 className="display-5">{d.title}</h1>
                <p className="lead">{d.subtitle}</p>
                {d.btnText && <a className="btn btn-primary" href={d.btnHref||"#"}>{d.btnText}</a>}
            </div>
            <div className="container py-5">
                {d.avatarUrl && (
                    <img src={d.avatarUrl}
                         alt=""
                         className="rounded-circle mb-3"
                         style={{width:120,height:120,objectFit:"cover",border:"4px solid #fff"}} />
                )}
                <h1>{d.title}</h1>
                <p className="lead">{d.subtitle}</p>
                {d.btnText && <a href={d.btnHref} className="btn btn-primary">{d.btnText}</a>}
            </div>
        </section>
    );
}