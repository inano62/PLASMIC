type HeroProps = {
    headline: string;
    subhead: string;
    bullets: string[];
    trust: string[];
    reserveHref: string;
};

export function Hero({ headline, subhead, bullets, trust, reserveHref }: HeroProps) {
    return (
        <section className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-16 pb-12">
            <div className="grid lg:grid-cols-2 gap-10 items-center">
                <div>
                    <h1 className="text-4xl md:text-5xl font-extrabold leading-tight tracking-tight">{headline}</h1>
                    <p className="mt-5 text-lg text-slate-600">{subhead}</p>
                    <div className="mt-8 flex flex-wrap gap-3">
                        <a id="cta" href={reserveHref} className="px-5 py-3 rounded-2xl bg-indigo-600 text-white hover:bg-indigo-700">相談する</a>
                        <a href="#pricing" className="px-5 py-3 rounded-2xl border border-slate-300 hover:bg-slate-50">料金を見る</a>
                    </div>
                    <div className="mt-6 flex flex-wrap gap-2">
                        {trust.map(t => (
                            <span key={t} className="text-xs px-2 py-1 rounded-full bg-slate-100 border border-slate-200">{t}</span>
                        ))}
                    </div>
                </div>
                <div className="relative">
                    <div className="absolute -inset-4 rounded-3xl bg-gradient-to-tr from-indigo-200/60 to-fuchsia-200/60 blur-2xl"/>
                    <div className="relative rounded-3xl border border-slate-200 shadow-xl bg-white p-4">
                        <div className="aspect-video w-full rounded-2xl bg-slate-100 grid place-items-center text-slate-500">
                            {/*<span>Product Preview</span>*/}
                            <img src="/reserve.bmp" alt="" className="w-full h-auto rounded-2xl object-cover" />
                        </div>
                        <ul className="mt-4 grid grid-cols-2 gap-2 text-sm">
                            {bullets.map(b => (
                                <li key={b} className="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200">{b}</li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    );
}
