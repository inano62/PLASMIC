import { Outlet } from "react-router-dom";
import { useSiteSettings } from "../lib/siteSettings";


export default function SiteLayout() {
    const s = useSiteSettings();
    return (
        <div className="min-h-screen flex flex-col bg-gradient-to-b from-white to-slate-50 text-slate-800">
            {/*<header className="sticky top-0 z-40 backdrop-blur border-b border-slate-200/70 bg-white/70">*/}
            {/*    <div className="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">*/}
            {/*        <Link to="/" className="flex items-center gap-3">*/}
            {/*            {s.logoUrl ? (*/}
            {/*                <img src={s.logoUrl} className="h-8 w-8 rounded-lg object-cover" alt="logo" />*/}
            {/*            ) : (*/}
            {/*                <div className="h-8 w-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-500" />*/}
            {/*            )}*/}
            {/*            <span className="font-semibold tracking-wide">{s.brandName || "Regal"}</span>*/}
            {/*        </Link>*/}
            {/*        <nav className="flex items-center gap-6 text-sm">*/}
            {/*            <NavLink to="/" className={({isActive}) => isActive?"text-indigo-600":"hover:text-indigo-600"}>ホーム</NavLink>*/}
            {/*            <NavLink to="/reserve" className={({isActive}) => isActive?"text-indigo-600":"hover:text-indigo-600"}>予約</NavLink>*/}
            {/*            <a href="#features" className="hover:text-indigo-600">機能</a>*/}
            {/*            <a href="#pricing" className="hover:text-indigo-600">料金</a>*/}
            {/*            <Link to="/admin" className="rounded-xl px-3 py-1.5 bg-indigo-600 text-white hover:bg-indigo-500">管理</Link>*/}
            {/*        </nav>*/}
            {/*    </div>*/}
            {/*</header>*/}


            <main className="flex-1"><Outlet /></main>


            <footer className="mt-14 border-t border-slate-200/70">
                <div className="max-w-7xl mx-auto px-4 py-10 text-sm text-slate-500 flex flex-col md:flex-row items-center justify-between gap-3">
                    <p>© {new Date().getFullYear()} {s.brandName || "Regal"}. All rights reserved.</p>
                    <div className="flex items-center gap-4">
                        <a className="hover:text-slate-700" href="#">利用規約</a>
                        <a className="hover:text-slate-700" href="#">プライバシー</a>
                        <a className="hover:text-slate-700" href="#">特商法表記</a>
                    </div>
                </div>
            </footer>
        </div>
    );
}