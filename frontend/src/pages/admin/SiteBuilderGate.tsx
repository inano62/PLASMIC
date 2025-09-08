// SiteBuilderGate.tsx
import { useEffect, useState } from 'react';

export default function SiteBuilderGate() {
    const [state, setState] = useState<'loading'|'entitled'|'paywall'>('loading');

    useEffect(() => {
        fetch('/api/sitebuilder/status', { credentials: 'include' })
            .then(r => r.json())
            .then(d => setState(d.entitled ? 'entitled' : 'paywall'))
            .catch(() => setState('paywall'));
    }, []);

    if (state === 'loading') return <div>読み込み中…</div>;
    if (state === 'entitled') {
        return <SiteBuilder />; // 既存のビルダーReactをここで表示
    }

    // ペイウォール
    async function buy() {
        const r = await fetch('/api/sitebuilder/checkout', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        const j = await r.json();
        location.href = j.url; // Stripe へ遷移
    }

    return (
        <div className="card">
            <div className="card-body">
                <h4 className="mb-2">Site Builder のご利用には購入が必要です</h4>
                <p>価格: ¥19,800（買い切り）</p>
                <button className="btn btn-primary" onClick={buy}>Stripeで購入する</button>
            </div>
        </div>
    );
}
