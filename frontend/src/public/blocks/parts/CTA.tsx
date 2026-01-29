// src/public/blocks/parts/CTA.tsx
interface CTAProps {
    d?: {
        text?: string;
        btnHref?: string;
        btnText?: string;
    };
}

export default function CTA({ d }: CTAProps) {
    return (
        <section className="cta">
            <div className="cta-text">{d?.text ?? 'お問い合わせ'}</div>
            <a className="btn" href={d?.btnHref ?? '#'}>{d?.btnText ?? '送信'}</a>
        </section>
    );
}