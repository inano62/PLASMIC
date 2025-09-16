export function Header({ badge, vertical }:{ badge?: string; vertical: string }) {
    return (
        <header className="sticky top-0 z-40 backdrop-blur bg-white/70 border-b border-slate-200">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="h-8 w-8 rounded-2xl bg-gradient-to-br from-fuchsia-500 to-indigo-500"/>
                    <span className="font-semibold tracking-tight">PLASMIC</span>
                    {vertical !== "default" && (
                        <span className="ml-2 text-xs rounded-full px-2 py-0.5 bg-slate-100 border border-slate-200">{badge}</span>
                    )}
                </div>
                <nav className="hidden md:flex items-center gap-6 text-sm">
                    <a href="#features" className="hover:text-slate-600">機能</a>
                    <a href="#pricing" className="hover:text-slate-600">料金</a>
                    <a href="#security" className="hover:text-slate-600">セキュリティ</a>
                    <a href="#faq" className="hover:text-slate-600">FAQ</a>
                </nav>
                <div className="flex items-center gap-3">
                    <a href="/admin" className="text-sm px-3 py-1.5 rounded-xl border border-slate-300 hover:bg-slate-50">ログイン</a>
                    <a href="#cta" className="text-sm px-3 py-1.5 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">詳細を見る</a>
                </div>
            </div>
        </header>
    );
}
