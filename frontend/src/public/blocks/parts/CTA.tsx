// src/public/blocks/parts/CTA.tsx
export default function CTA({ d }: { d: any }){
    return (
        <section className="cta">
            <div className="cta-text">{d?.text ?? 'お問い合わせ'}</div>
            <a className="btn" href={d?.btnHref ?? '#'}>{d?.btnText ?? '送信'}</a>
        </section>
    );
}