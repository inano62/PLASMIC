export function Footer() {
    return(
        <footer className="border-t border-slate-200 py-10">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <div className="text-sm text-slate-500">© {new Date().getFullYear()} PLASMIC</div>
                <div className="text-xs text-slate-400">
                    このページは縦展開対応テンプレートです：/law, /beauty, /edu または ?v=law 等で文言が自動最適化されます。
                </div>
            </div>
        </footer>
    )
}